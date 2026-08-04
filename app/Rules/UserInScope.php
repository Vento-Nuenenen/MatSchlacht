<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UserInScope implements ValidationRule
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

        /** @var int|string $value */
        $targetUser = User::find($value);
        if (! $targetUser) {
            return;
        }

        $sharedRoles = $user->roles->intersect($targetUser->roles);
        if ($sharedRoles->isEmpty()) {
            $fail('Die ausgewählte Person ist nicht in Ihrem Bereich.');
        }
    }
}
