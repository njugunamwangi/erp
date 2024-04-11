<?php

namespace App\Filament\Resources\MpesaSTKResource\Pages;

use App\Filament\Resources\MpesaSTKResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMpesaSTK extends ViewRecord
{
    protected static string $resource = MpesaSTKResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
