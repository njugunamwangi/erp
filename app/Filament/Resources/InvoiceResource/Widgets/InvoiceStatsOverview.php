<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\InvoiceStatus;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Invoices by Numbers', Invoice::all()->count())
                ->color('primary')
                ->description('Number of invoices generated')
                ->descriptionIcon('heroicon-o-calculator'),
            Stat::make('Invoices Total amount', Number::currency(Invoice::all()->sum('subtotal'), 'Kes'))
                ->color('warning')
                ->description('Exclusive of taxes')
                ->descriptionIcon('heroicon-o-banknotes'),
            Stat::make('Paid Invoices', Invoice::where('status', '=', InvoiceStatus::Paid)->count())
                ->color('success')
                ->description('Number of invoices paid')
                ->descriptionIcon('heroicon-o-check-badge'),
        ];
    }
}
