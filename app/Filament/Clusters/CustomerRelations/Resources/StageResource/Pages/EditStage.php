<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\StageResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\StageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStage extends EditRecord
{
    protected static string $resource = StageResource::class;

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
