<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description'
    ];
}
