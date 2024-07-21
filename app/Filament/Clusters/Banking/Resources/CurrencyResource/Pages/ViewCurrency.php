<?php

namespace App\Filament\Clusters\Banking\Resources\CurrencyResource\Pages;

use App\Filament\Clusters\Banking\Resources\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCurrency extends ViewRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}