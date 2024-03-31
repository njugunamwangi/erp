<?php

namespace App;

enum InvoiceStatus: string
{
    case Paid = 'Paid';
    case Unpaid = 'Unpaid';

    public function getColor(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Unpaid => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Paid => 'heroicon-o-check-circle',
            self::Unpaid => 'heroicon-o-x-circle',
        };
    }
}