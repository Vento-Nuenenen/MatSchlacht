<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Spatie\Permission\Models\Role;

class RoleInScope implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();
        if ($user->isPrivileged()) {
            return;
        }

        $role = Role::find($value);
        if (! $role) {
            return;
        }

        if (! $user->hasRole($role->name)) {
            $fail('Sie haben keine Berechtigung für diese Einheit zu bestellen.');
        }
    }
}
