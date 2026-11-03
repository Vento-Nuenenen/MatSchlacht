<?php

namespace App\Models;

use Database\Factories\HitobitoGroupRoleMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Gives people of a Hitobito group, optionally only with a certain Hitobito role, an app role.
 * It does not admit anybody, that is up to the access rules.
 *
 * @property string $hitobito_group_id
 * @property string|null $hitobito_role_class
 * @property string $role_name
 */
#[Fillable([
    'hitobito_group_id',
    'hitobito_role_class',
    'role_name',
])]
class HitobitoGroupRoleMapping extends Model
{
    /** @use HasFactory<HitobitoGroupRoleMappingFactory> */
    use HasFactory;

    /**
     * Whether the mapping applies to a Hitobito profile.
     *
     * @param  array<string, mixed>  $profile
     */
    public function matches(array $profile): bool
    {
        /** @var array<int, array<string, mixed>> $roles */
        $roles = is_array($profile['roles'] ?? null) ? $profile['roles'] : [];

        return collect($roles)->contains(fn (array $role): bool => $this->hitobito_group_id === (string) ($role['group_id'] ?? '')
            && ($this->hitobito_role_class === null || $this->hitobito_role_class === ($role['role_class'] ?? null)));
    }
}
