<?php

namespace App\Enums;

enum QuoteSeries: string
{
    case IN2QUT = 'IN2QUT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
