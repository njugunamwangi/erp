<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Invoice')
                ->icon('heroicon-o-pencil-square'),
            Action::make('pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('success')
                ->url(URL::signedRoute('invoice.download', [$this->record->id]), true)
                ->openUrlInNewTab()
        ];
    }
}
