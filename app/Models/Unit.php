<?php

namespace App\Models;

use Database\Factories\UnitsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class Unit extends Model
{
    /** @use HasFactory<UnitsFactory> */
    use HasFactory, HasRoles;

    protected $fillable = [
        'name',
        'description',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
