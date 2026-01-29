<?php

namespace App\States\Order;

class Stored extends OrderState
{
    public static string $name = 'stored';

    public function display(): string
    {
        return 'Entwurf';
    }

    public function color(): string
    {
        return 'orange';
    }
}
