<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Filament\Resources\QuoteResource\Widgets\QuoteOverviewStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-squares-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuoteOverviewStats::class,
        ];
    }
}
