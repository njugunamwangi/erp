<?php

namespace App\Filament\Clusters\Assets\Resources\ServiceResource\Pages;

use App\Filament\Clusters\Assets\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
