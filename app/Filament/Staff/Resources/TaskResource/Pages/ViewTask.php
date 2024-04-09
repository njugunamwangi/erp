<?php

namespace App\Filament\Staff\Resources\TaskResource\Pages;

use App\Filament\Staff\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
