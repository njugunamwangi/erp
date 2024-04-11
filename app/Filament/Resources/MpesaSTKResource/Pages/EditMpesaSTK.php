<?php

namespace App\Filament\Resources\MpesaSTKResource\Pages;

use App\Filament\Resources\MpesaSTKResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMpesaSTK extends EditRecord
{
    protected static string $resource = MpesaSTKResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
