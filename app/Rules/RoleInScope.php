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
        /** @var int|string $value */
        $role = Role::find($value);
        if (! $role) {
            return;
        }

        if (! auth()->user()->canActForRole($role)) {
            $fail(__('Sie haben keine Berechtigung für diese Einheit zu bestellen.'));
        }
    }
}
