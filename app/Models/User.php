<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'azure_id',
        'firstname',
        'lastname',
        'scoutname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function fullName(): string
    {
        $civil_name = $this->firstname.' '.$this->lastname;

        if ($this->scoutname) {
            $civil_name .= ' / '.$this->scoutname;
        }

        return $civil_name;
    }

    public function preferredName()
    {
        if ($this->scoutname) {
            $preferred_name = $this->scoutname;
        } else {
            $preferred_name = $this->firstname;
        }

        return $preferred_name;
    }

    public function isPrivileged(): bool
    {
        return $this->hasAnyRole(['Materialwart']);
    }
}
