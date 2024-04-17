<?php

namespace App\Enums;

enum EquipmentType: string
{
    case Drone = 'Drone';
    case RTK = 'RTK';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
