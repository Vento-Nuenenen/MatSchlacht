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
        /** @var int|string $value */
        $targetUser = User::find($value);
        if (! $targetUser) {
            return;
        }

        if (! auth()->user()->canActForUser($targetUser)) {
            $fail('Die ausgewählte Person ist nicht in Ihrem Bereich.');
        }
    }
}
