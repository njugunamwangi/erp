<?php

namespace App\Filament\Resources\VerticalResource\Pages;

use App\Filament\Resources\VerticalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerticals extends ListRecords
{
    protected static string $resource = VerticalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
