<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use App\QuoteSeries;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Quote')
                ->icon('heroicon-o-pencil-square'),
            Action::make('pdf')
                ->label('Download Quote')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('success')
                ->url(URL::signedRoute('quote.download', [$this->record->id]), true)
                ->openUrlInNewTab(),
            Action::make('viewInvoice')
                ->label('View Invoice')
                ->visible(fn($record) => $record->invoice)
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url(fn($record) => InvoiceResource::getUrl('view', ['record' => $record->invoice->id])),
            Action::make('generateInvoice')
                ->label('Generate Invoice')
                ->color('warning')
                ->visible(fn($record) => !$record->invoice)
                ->icon('heroicon-o-document-duplicate')
                ->modalSubmitActionLabel('Generate Invoice')
                ->form([
                    Select::make('series')
                        ->required()
                        ->enum(InvoiceSeries::class)
                        ->options(InvoiceSeries::class)
                        ->searchable()
                        ->preload()
                        ->default(InvoiceSeries::IN2INV->name)
                ])
                ->action(function(array $data, $record) {
                    Invoice::create([
                        'user_id' => $record->user_id,
                        'quote_id' => $record->id,
                        'items' => $record->items,
                        'subtotal' => $record->subtotal,
                        'taxes' => $record->taxes,
                        'total' => $record->total,
                        'serial_number' => $serial_number = (Invoice::max('serial_number') ?? 0) + 1,
                        'serial' => $data['series'] . '-' . str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                    ]);

                    $recipients = User::role(Role::ADMIN)->get();

                    foreach($recipients as $recipient) {
                        Notification::make()
                            ->title('Invoice generated')
                            ->body('Invoice successfully generated')
                            ->icon('heroicon-o-check-badge')
                            ->color('success')
                            ->sendToDatabase($recipient);
                    }
                }),
        ];
    }
}
