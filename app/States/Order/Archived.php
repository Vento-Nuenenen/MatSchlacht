<?php

namespace App\States\Order;

class Archived extends OrderState
{
    public static string $name = 'archived';

    public function display(): string
    {
        return 'Archiviert';
    }

    public function color(): string
    {
        return 'dark-gray';
    }
}
