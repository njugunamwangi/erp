<?php

namespace App\Filament\Resources\StageResource\Pages;

use App\Filament\Resources\StageResource;
use App\Models\Stage;
use Filament\Actions;
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
