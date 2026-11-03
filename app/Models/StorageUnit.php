<?php

namespace App\Models;

use App\Enums\StorageLevel;
use Database\Factories\StorageUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A node of the storage hierarchy: Lagerort, Lagerraum, Lagerregal, Regalebene or Lagerplatz.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property StorageLevel $level
 * @property string $name
 * @property string|null $description
 */
#[Fillable([
    'parent_id',
    'level',
    'name',
    'description',
])]
class StorageUnit extends Model
{
    /** @use HasFactory<StorageUnitFactory> */
    use HasFactory;

    /**
     * Eager loads the ancestors needed to build the path of a unit.
     */
    public const ANCESTORS = 'parent.parent.parent.parent';

    /**
     * Eager loads all descendants (the hierarchy has at most five levels).
     */
    public const DESCENDANTS = 'children.children.children.children';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => StorageLevel::class,
        ];
    }

    /**
     * @return BelongsTo<StorageUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(StorageUnit::class, 'parent_id');
    }

    /**
     * @return HasMany<StorageUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(StorageUnit::class, 'parent_id')->orderBy('name');
    }

    /**
     * @return HasMany<ItemStock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    /**
     * @param  Builder<StorageUnit>  $query
     * @return Builder<StorageUnit>
     */
    public function scopeOfLevel(Builder $query, StorageLevel $level): Builder
    {
        return $query->where('level', $level->value);
    }

    /**
     * The ancestors from the top level down to the direct parent.
     *
     * @return array<int, StorageUnit>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $unit = $this->parent;

        while ($unit !== null) {
            array_unshift($ancestors, $unit);
            $unit = $unit->parent;
        }

        return $ancestors;
    }

    /**
     * The full path, e.g. "Pfadiheim › Keller › Regal A › Fach 3".
     */
    public function path(string $separator = ' › '): string
    {
        return collect([...$this->ancestors(), $this])->pluck('name')->implode($separator);
    }

    /**
     * Compare two units by their position in the hierarchy, level by level and in natural order
     * ("Fach 2" before "Fach 10"), so that units close to each other are sorted next to each other.
     */
    public static function compareByPath(StorageUnit $first, StorageUnit $second): int
    {
        $firstNames = array_map(fn (StorageUnit $unit) => $unit->name, [...$first->ancestors(), $first]);
        $secondNames = array_map(fn (StorageUnit $unit) => $unit->name, [...$second->ancestors(), $second]);

        foreach ($firstNames as $index => $name) {
            if (! array_key_exists($index, $secondNames)) {
                return 1;
            }

            $comparison = strnatcasecmp($name, $secondNames[$index]);

            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return count($firstNames) <=> count($secondNames);
    }

    /**
     * This unit followed by all of its descendants (depth first).
     *
     * @return Collection<int, StorageUnit>
     */
    public function withDescendants(): Collection
    {
        $this->loadMissing(self::DESCENDANTS);

        $units = [$this];

        foreach ($this->children as $child) {
            array_push($units, ...$child->withDescendants()->all());
        }

        return new Collection($units);
    }
}
