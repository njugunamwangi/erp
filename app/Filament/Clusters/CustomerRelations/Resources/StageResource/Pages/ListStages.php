<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\StageResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\StageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStages extends ListRecords
{
    protected static string $resource = StageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
