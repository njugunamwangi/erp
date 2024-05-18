<?php

namespace App\Enums;

enum InvoiceSeries: string
{
    case IN2INV = 'IN2INV';

    public const DEFAULT = self::IN2INV->value;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
