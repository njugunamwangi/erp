<?php

namespace App\Filament\Clusters\Banking\Resources\AccountResource\Pages;

use App\Filament\Clusters\Banking\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
