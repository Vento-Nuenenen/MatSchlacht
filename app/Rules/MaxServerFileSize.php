<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;

class MaxServerFileSize implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $maxSize = $this->getServerMaxSize();

        if ($value->getSize() > $maxSize) {
            $fail(__('Die Datei :attribute ist zu groß. Das Server-Limit liegt bei ').($maxSize / 1024 / 1024).'MB.');
        }
    }

    /**
     * Get the server maximum upload size in bytes.
     */
    protected function getServerMaxSize(): int
    {
        $iniVal = ini_get('upload_max_filesize');
        $val = trim($iniVal ?: '');

        if ($val === '') {
            return 0;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $valInt = (int) $val;

        switch ($last) {
            case 'g':
                $valInt *= 1024;
                // no break
            case 'm':
                $valInt *= 1024;
                // no break
            case 'k':
                $valInt *= 1024;
        }

        return $valInt;
    }
}
