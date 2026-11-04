<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Spatie\Permission\Models\Role;

class CanAssignRole implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        // Materialwart can assign any role
        if ($user->hasRole('Materialwart')) {
            return;
        }

        // Check if role exists
        $role = Role::where('name', $value)->first();
        if (! $role) {
            return;
        }

        // Non-Materialwart can only assign roles they have themselves
        if (! $user->hasRole($role->name)) {
            $fail(__('Sie haben keine Berechtigung, die Rolle "').$value.'" zuzuweisen.');
        }
    }
}
