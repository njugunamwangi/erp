<?php

namespace App\Filament\Resources\VerticalResource\Pages;

use App\Filament\Resources\VerticalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVertical extends ViewRecord
{
    protected static string $resource = VerticalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-square-plus'),
        ];
    }
}
