<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['serial_number'] = (Invoice::max('serial_number') ?? 0) + 1;
        $data['serial'] = $data['series'] . '-' . str_pad($data['serial_number'], 5, '0', STR_PAD_LEFT);

        // dd($data);
        return $data;
    }
}
