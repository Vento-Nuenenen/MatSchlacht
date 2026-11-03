<?php

namespace App\Services;

use App\Models\StorageUnit;
use Illuminate\Support\Str;

/**
 * Builds the short codes of the storage hierarchy and the generated names of the bins,
 * e.g. "PFA-KEL-RA-E1-01" for the first bin of Pfadiheim › Keller › Regal A › Ebene 1.
 */
class StorageCodeGenerator
{
    public const int MAX_LENGTH = 10;

    public const string SEPARATOR = '-';

    /**
     * The number of letters taken from a name that consists of a single word.
     */
    protected const int SINGLE_WORD_LENGTH = 3;

    /**
     * Derive a code from a name: "Pfadiheim" becomes "PFA", "Regal A" becomes "RA" and "Fach 12" becomes "F12".
     */
    public function fromName(string $name): string
    {
        $words = preg_split('/[^A-Z0-9]+/', Str::upper(Str::ascii($name, 'de')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return 'X';
        }

        if (count($words) === 1 && ! ctype_digit($words[0])) {
            return substr($words[0], 0, self::SINGLE_WORD_LENGTH);
        }

        $parts = array_map(fn (string $word): string => ctype_digit($word) ? $word : $word[0], $words);

        return substr(implode('', $parts), 0, self::MAX_LENGTH);
    }

    /**
     * Make a code unique among the units of the same parent: "KEL", "KEL2", "KEL3".
     */
    public function uniqueCode(string $code, ?int $parentId, ?int $ignoredUnitId = null): string
    {
        $usedCodes = StorageUnit::where('parent_id', $parentId)
            ->when($ignoredUnitId !== null, fn ($query) => $query->whereKeyNot($ignoredUnitId))
            ->pluck('code')
            ->all();

        $uniqueCode = $code;

        for ($suffix = 2; in_array($uniqueCode, $usedCodes, true); $suffix++) {
            $uniqueCode = substr($code, 0, self::MAX_LENGTH - strlen((string) $suffix)).$suffix;
        }

        return $uniqueCode;
    }

    /**
     * The next free number of a shelf tier, e.g. "03".
     */
    public function nextBinNumber(?int $tierId): string
    {
        $highestNumber = StorageUnit::where('parent_id', $tierId)
            ->pluck('code')
            ->map(fn (?string $code): int => (int) $code)
            ->max() ?? 0;

        return str_pad((string) ($highestNumber + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * The name of a bin: the codes of all its ancestors followed by its own.
     */
    public function binName(StorageUnit $bin): string
    {
        return collect([...$bin->ancestors(), $bin])->pluck('code')->implode(self::SEPARATOR);
    }
}
