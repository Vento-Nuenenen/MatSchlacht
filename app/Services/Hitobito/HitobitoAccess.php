<?php

namespace App\Services\Hitobito;

use App\Models\HitobitoAccessRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Spatie\Permission\Models\Role;

/**
 * Decides what a Hitobito profile may do, for the real login as well as for the test page.
 */
class HitobitoAccess
{
    /**
     * The rules that apply to the profile.
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
     * The rules that admit the profile, none means the person is refused.
     *
     * @param  array<string, mixed>  $profile
     * @return Collection<int, HitobitoAccessRule>
     */
    public function admittingRules(array $profile): Collection
    {
        return $this->matchingRules($profile)
            ->filter(fn (HitobitoAccessRule $rule): bool => $rule->grants_access)
            ->values();
    }

    /**
     * The existing app roles that the rules give to the profile.
     *
     * @param  array<string, mixed>  $profile
     * @return BaseCollection<int, string>
     */
    public function appRoles(array $profile): BaseCollection
    {
        $roleNames = $this->matchingRules($profile)->pluck('role_name')->filter()->unique();

        return Role::whereIn('name', $roleNames)->orderBy('name')->pluck('name');
    }
}
