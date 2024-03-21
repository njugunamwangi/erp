<?php

namespace App\Filament\Resources\CustomFieldResource\Pages;

use App\Filament\Resources\CustomFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomField extends ViewRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
