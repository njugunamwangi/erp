<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Mail\SendInvoice;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['serial_number'] = (Invoice::max('serial_number') ?? 0) + 1;
        $data['serial'] = $data['series'].'-'.str_pad($data['serial_number'], 5, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function afterCreate(): void
    {
        $invoice = $this->getRecord();

        if ($invoice->mail) {

            $invoice->savePdf();

            Mail::to($invoice->user->email)->send(new SendInvoice($invoice));

            $name = 'invoice_'.$invoice->series->name.'_'.str_pad($invoice->serial_number, 5, '0', STR_PAD_LEFT).'.pdf';

            Storage::disk('invoices')->delete($name);
        }
    }
}
