<?php

namespace App\States\Order;

class Delivered extends OrderState
{
    public static string $name = 'delivered';

    public function display(): string
    {
        return 'Ausgegeben';
    }

    public function color(): string
    {
        return 'dark-green';
    }
}
