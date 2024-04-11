<?php

namespace App\Filament\Resources\MpesaSTKResource\Pages;

use App\Filament\Resources\MpesaSTKResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMpesaSTKS extends ListRecords
{
    protected static string $resource = MpesaSTKResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
