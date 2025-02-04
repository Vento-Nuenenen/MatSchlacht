<?php

namespace App\Models;

use Database\Factories\ItemGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemGroup extends Model
{
    /** @use HasFactory<ItemGroupFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'position',
    ];
}
