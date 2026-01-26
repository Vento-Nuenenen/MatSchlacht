<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AzureGroupRoleMapping extends Model
{
    protected $fillable = [
        'azure_group_id',
        'role_name'
    ];
}
