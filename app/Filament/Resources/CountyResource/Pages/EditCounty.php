<?php

namespace App\Filament\Resources\CountyResource\Pages;

use App\Filament\Resources\CountyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounty extends EditRecord
{
    protected static string $resource = CountyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
