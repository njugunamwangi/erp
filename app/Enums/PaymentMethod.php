<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MPesa = 'MPesa';
    case PayStack = 'PayStack';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
