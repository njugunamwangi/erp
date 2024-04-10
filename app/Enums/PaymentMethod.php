<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MPesa = 'MPesa';
    case PayStack = 'PayStack';
}
