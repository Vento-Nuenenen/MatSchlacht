<?php

namespace App\States\Order;

class Accepted extends OrderState
{
    public static string $name = 'accepted';

    public function display(): string
    {
        return __('Bestätigt');
    }

    public function color(): string
    {
        return 'green';
    }
}
