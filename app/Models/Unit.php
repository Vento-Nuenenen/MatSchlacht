<?php

namespace App\Models;

use Database\Factories\UnitsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    /** @use HasFactory<UnitsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];
}
