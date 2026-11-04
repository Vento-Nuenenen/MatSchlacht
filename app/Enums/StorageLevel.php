<?php

namespace App\Enums;

/**
 * The levels of the storage hierarchy: Lagerort › Lagerraum › Lagerregal › Regalebene › Lagerplatz.
 */
enum StorageLevel: string
{
    case Location = 'location';
    case Room = 'room';
    case Shelf = 'shelf';
    case Tier = 'tier';
    case Bin = 'bin';

    public function label(): string
    {
        return match ($this) {
            self::Location => __('Lagerort'),
            self::Room => __('Lagerraum'),
            self::Shelf => __('Lagerregal'),
            self::Tier => __('Regalebene'),
            self::Bin => __('Lagerplatz'),
        };
    }

    public function pluralLabel(): string
    {
        return match ($this) {
            self::Location => __('Lagerorte'),
            self::Room => __('Lagerräume'),
            self::Shelf => __('Lagerregale'),
            self::Tier => __('Regalebenen'),
            self::Bin => __('Lagerplätze'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Location => 'fa-warehouse',
            self::Room => 'fa-door-open',
            self::Shelf => 'fa-table-cells',
            self::Tier => 'fa-layer-group',
            self::Bin => 'fa-box',
        };
    }

    /**
     * The level a unit of this level must be placed in, null for the top level.
     */
    public function parentLevel(): ?self
    {
        return match ($this) {
            self::Location => null,
            self::Room => self::Location,
            self::Shelf => self::Room,
            self::Tier => self::Shelf,
            self::Bin => self::Tier,
        };
    }

    /**
     * The level of the units this level contains, null for the bottom level.
     */
    public function childLevel(): ?self
    {
        return match ($this) {
            self::Location => self::Room,
            self::Room => self::Shelf,
            self::Shelf => self::Tier,
            self::Tier => self::Bin,
            self::Bin => null,
        };
    }

    /**
     * Only bins (Lagerplätze) hold stock.
     */
    public function holdsStock(): bool
    {
        return $this === self::Bin;
    }
}
