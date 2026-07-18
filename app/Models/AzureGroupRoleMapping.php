<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'azure_group_id',
    'role_name',
])]
class AzureGroupRoleMapping extends Model
{
    //
}
