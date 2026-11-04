<?php

namespace App\Models;

use Database\Factories\HitobitoAccessRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admits people to the Hitobito login and/or gives them an app role. All filled criteria
 * have to apply, the group, role and permission even on one and the same Hitobito role.
 *
 * @property string|null $hitobito_person_id
 * @property string|null $hitobito_group_id
 * @property string|null $hitobito_role_class
 * @property string|null $hitobito_permission
 * @property string|null $role_name
 * @property bool $grants_access
 * @property string|null $description
 */
#[Fillable([
    'hitobito_person_id',
    'hitobito_group_id',
    'hitobito_role_class',
    'hitobito_permission',
    'role_name',
    'grants_access',
    'description',
])]
class HitobitoAccessRule extends Model
{
    /** @use HasFactory<HitobitoAccessRuleFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'grants_access' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grants_access' => 'boolean',
        ];
    }

    /**
     * Whether the rule applies to a Hitobito profile. A rule without criteria never applies.
     *
     * @param  array<string, mixed>  $profile
     */
    public function matches(array $profile): bool
    {
        if ($this->hitobito_person_id !== null && $this->hitobito_person_id !== (string) ($profile['id'] ?? '')) {
            return false;
        }

        if ($this->hitobito_group_id === null && $this->hitobito_role_class === null && $this->hitobito_permission === null) {
            return $this->hitobito_person_id !== null;
        }

        /** @var array<int, array<string, mixed>> $roles */
        $roles = is_array($profile['roles'] ?? null) ? $profile['roles'] : [];

        return collect($roles)->contains(fn (array $role): bool => $this->matchesRole($role));
    }

    /**
     * @param  array<string, mixed>  $role
     */
    protected function matchesRole(array $role): bool
    {
        $permissions = is_array($role['permissions'] ?? null) ? $role['permissions'] : [];

        return ($this->hitobito_group_id === null || $this->hitobito_group_id === (string) ($role['group_id'] ?? ''))
            && ($this->hitobito_role_class === null || $this->hitobito_role_class === ($role['role_class'] ?? null))
            && ($this->hitobito_permission === null || in_array($this->hitobito_permission, $permissions, true));
    }
}
