<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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
