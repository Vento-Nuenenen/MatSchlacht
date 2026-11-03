<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case Inspection = 'inspection';
    case Cleaning = 'cleaning';
    case Repair = 'repair';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Inspection => 'Kontrolle',
            self::Cleaning => 'Reinigung',
            self::Repair => 'Reparatur',
            self::Other => 'Sonstiges',
        };
    }
}
