<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\StageResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\StageResource;
use App\Models\Stage;
use Filament\Resources\Pages\CreateRecord;

class CreateStage extends CreateRecord
{
    protected static string $resource = StageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['position'] = Stage::max('position') + 1;

        return $data;
    }
}
