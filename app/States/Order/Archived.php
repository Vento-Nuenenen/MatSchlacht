<?php

namespace App\States\Order;

class Archived extends OrderState
{
    public static string $name = 'archived';

    public function display(): string
    {
        return __('Archiviert');
    }

    public function color(): string
    {
        return 'dark-gray';
    }
}
