<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'azure_id',
    'hitobito_id',
    'firstname',
    'lastname',
    'scoutname',
    'email',
    'password',
    'locale',
    'aktiv',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Matches the default of the column, for accounts that were just created.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'aktiv' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktiv' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The language of the user, also used for the mails to the user.
     */
    public function preferredLocale(): ?string
    {
        return array_key_exists((string) $this->locale, config('app.available_locales')) ? $this->locale : null;
    }

    public function fullName(bool $withScoutName = true): string
    {
        $civil_name = $this->firstname.' '.$this->lastname;

        if ($this->scoutname && $withScoutName === true) {
            $civil_name .= ' / '.$this->scoutname;
        }

        return (string) $civil_name;
    }

    /**
     * The initials shown in the avatar, e.g. "MM" for Max Muster.
     */
    public function initials(): string
    {
        $initials = mb_substr((string) $this->firstname, 0, 1).mb_substr((string) $this->lastname, 0, 1);

        return mb_strtoupper($initials !== '' ? $initials : mb_substr((string) $this->email, 0, 1));
    }

    public function preferredName(): string
    {
        if ($this->scoutname) {
            $preferred_name = $this->scoutname;
        } else {
            $preferred_name = $this->firstname;
        }

        return (string) $preferred_name;
    }

    public function isPrivileged(): bool
    {
        return $this->hasAnyRole(['Materialwart']);
    }

    /**
     * The roles (units) this user may act on behalf of.
     *
     * @return Collection<int, Role>
     */
    public function scopedRoles(): Collection
    {
        if ($this->isPrivileged()) {
            return Role::all();
        }

        return Role::whereKey($this->roles->modelKeys())->get();
    }

    /**
     * Query for the users this user is allowed to see and order for.
     *
     * @return Builder<User>
     */
    public function scopedUsersQuery(): Builder
    {
        if ($this->isPrivileged()) {
            return User::query();
        }

        $roleIds = $this->roles->pluck('id');

        return User::whereHas('roles', function (Builder $query) use ($roleIds): void {
            $query->whereIn('roles.id', $roleIds);
        });
    }

    public function canActForRole(Role $role): bool
    {
        return $this->isPrivileged() || $this->hasRole($role->name);
    }

    public function canActForUser(User $user): bool
    {
        return $this->isPrivileged() || $this->roles->intersect($user->roles)->isNotEmpty();
    }

    /**
     * Whether this user may change the account of the given user. Privileged accounts are
     * out of reach for everybody else, even when they share a role.
     */
    public function canManageUser(User $user): bool
    {
        return $this->isPrivileged() || (! $user->isPrivileged() && $this->canActForUser($user));
    }

    /**
     * The roles to give the target user: the selected ones, plus those the target has
     * outside of the scope of this user. These are not part of the form of this user.
     *
     * @param  array<int, string>  $roleNames
     * @return array<int, string>
     */
    public function rolesIncludingUnmanaged(User $target, array $roleNames): array
    {
        $unmanagedRoles = $target->roles->pluck('name')->diff($this->scopedRoles()->pluck('name'));

        return [...$roleNames, ...$unmanagedRoles->values()];
    }

    public function isAzureUser(): bool
    {
        return ! empty($this->azure_id);
    }

    public function isHitobitoUser(): bool
    {
        return ! empty($this->hitobito_id);
    }

    /**
     * Users of an external login have no password in this app.
     */
    public function isExternalUser(): bool
    {
        return $this->isAzureUser() || $this->isHitobitoUser();
    }
}
