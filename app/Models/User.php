<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'azure_id',
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

    /*
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /*
     * @return string
     */
    public function fullName(bool $withScoutName = true): string
    {
        $civil_name = $this->firstname.' '.$this->lastname;

        if ($this->scoutname && $withScoutName === true) {
            $civil_name .= ' / '.$this->scoutname;
        }

        return (string) $civil_name;
    }

    /*
     * @return string
     */
    public function preferredName(): string
    {
        if ($this->scoutname) {
            $preferred_name = $this->scoutname;
        } else {
            $preferred_name = $this->firstname;
        }

        return (string) $preferred_name;
    }

    /*
     * @return bool
     */
    public function isPrivileged(): bool
    {
        return $this->hasAnyRole(['Materialwart']);
    }

    /*
     * @return bool
     */
    public function isAzureUser(): bool
    {
        return ! empty($this->azure_id);
    }
}
