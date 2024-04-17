<?php

namespace App\Enums;

enum InvoiceSeries: string
{
    case IN2INV = 'IN2INV';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
