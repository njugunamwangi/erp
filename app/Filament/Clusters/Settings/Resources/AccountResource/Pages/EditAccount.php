<?php

namespace App\Filament\Clusters\Settings\Resources\AccountResource\Pages;

use App\Filament\Clusters\Settings\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}