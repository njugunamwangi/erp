<?php

namespace App\Filament\Resources\QuoteResource\Widgets;

use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuoteOverviewStats extends BaseWidget
{
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Quotes by Numbers', Quote::all()->count())
                ->color('success')
                ->description('Number of quotes generated')
                ->descriptionIcon('heroicon-o-calculator'),
            Stat::make('Quotes Total amount', 0)
                ->color('primary')
                ->description('Exclusive of taxes')
                ->descriptionIcon('heroicon-o-banknotes'),
        ];
    }
}
