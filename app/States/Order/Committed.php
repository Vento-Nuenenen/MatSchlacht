<?php

namespace App\States\Order;

class Committed extends OrderState
{
    public static string $name = 'committed';

    public function display(): string
    {
        return __('Bestellt');
    }

    public function color(): string
    {
        return 'red';
    }
}
