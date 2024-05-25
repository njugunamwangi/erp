<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Widgets\QuoteOverviewStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuoteOverviewStats::class,
        ];
    }
}
