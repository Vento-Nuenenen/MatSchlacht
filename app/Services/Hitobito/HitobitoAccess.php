<?php

namespace App\Services\Hitobito;

use App\Models\HitobitoAccessRule;
use App\Models\HitobitoGroupRoleMapping;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Spatie\Permission\Models\Role;

/**
 * Decides what a Hitobito profile may do, for the real login as well as for the test page.
 */
class HitobitoAccess
{
    /**
     * The access rules that admit the profile, none means the person is refused.
     *
     * @param  array<string, mixed>  $profile
     * @return Collection<int, HitobitoAccessRule>
     */
    public function matchingRules(array $profile): Collection
    {
        return HitobitoAccessRule::all()
            ->filter(fn (HitobitoAccessRule $rule): bool => $rule->matches($profile))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return Collection<int, HitobitoGroupRoleMapping>
     */
    public function matchingMappings(array $profile): Collection
    {
        return HitobitoGroupRoleMapping::all()
            ->filter(fn (HitobitoGroupRoleMapping $mapping): bool => $mapping->matches($profile))
            ->values();
    }

    /**
     * The existing app roles that the mappings and access rules give to the profile.
     *
     * @param  array<string, mixed>  $profile
     * @return BaseCollection<int, string>
     */
    public function appRoles(array $profile): BaseCollection
    {
        $roleNames = $this->matchingMappings($profile)->pluck('role_name')
            ->merge($this->matchingRules($profile)->pluck('role_name'))
            ->filter()
            ->unique();

        return Role::whereIn('name', $roleNames)->orderBy('name')->pluck('name');
    }
}
