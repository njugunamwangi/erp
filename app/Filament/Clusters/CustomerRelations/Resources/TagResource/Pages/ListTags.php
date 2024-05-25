<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\TagResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
