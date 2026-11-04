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
            self::Inspection => __('Kontrolle'),
            self::Cleaning => __('Reinigung'),
            self::Repair => __('Reparatur'),
            self::Other => __('Sonstiges'),
        };
    }
}
