<?php

namespace App\States\Order;

class Returned extends OrderState
{
    public static string $name = 'returned';

    public function display(): string
    {
        return __('Zurückgegeben');
    }

    public function color(): string
    {
        return 'blue';
    }
}
