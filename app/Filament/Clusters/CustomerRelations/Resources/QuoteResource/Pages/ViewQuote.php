<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Pages;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Mail\SendInvoice;
use App\Models\Invoice;
use App\Models\Note;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Wallo\FilamentSelectify\Components\ToggleButton;

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
                ->visible(fn ($record) => $record->invoice)
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url(fn ($record) => InvoiceResource::getUrl('view', ['record' => $record->invoice->id])),
            Action::make('generateInvoice')
                ->label('Generate Invoice')
                ->color('warning')
                ->visible(fn ($record) => ! $record->invoice)
                ->icon('heroicon-o-document-duplicate')
                ->modalSubmitActionLabel('Generate Invoice')
                ->form([
                    Select::make('series')
                        ->required()
                        ->enum(InvoiceSeries::class)
                        ->options(InvoiceSeries::class)
                        ->searchable()
                        ->preload()
                        ->default(InvoiceSeries::IN2INV->name),
                    ToggleButton::make('send')
                        ->label('Send Email to customer'),
                ])
                ->action(function (array $data, $record) {
                    $invoice = Invoice::create([
                        'user_id' => $record->user_id,
                        'quote_id' => $record->id,
                        'items' => $record->items,
                        'subtotal' => $record->subtotal,
                        'taxes' => $record->taxes,
                        'total' => $record->total,
                        'status' => InvoiceStatus::Unpaid,
                        'series' => $data['series'],
                        'serial_number' => $serial_number = Invoice::max('serial_number') + 1,
                        'serial' => $data['series'].'-'.str_pad($serial_number, 5, '0', STR_PAD_LEFT),
                        'currency_id' => $record->currency_id,
                        'notes' => Note::find(1)->invoices,
                        'mail' => $data['send']
                    ]);

                    if ($data['send'] == true) {

                        $invoice->savePdf();

                        Mail::to($invoice->user->email)->send(new SendInvoice($invoice));

                        $name = 'invoice_'.$invoice->series->name.'_'.str_pad($invoice->serial_number, 5, '0', STR_PAD_LEFT).'.pdf';

                        Storage::disk('invoices')->delete($name);
                    }

                    $recipients = User::role(Role::ADMIN)->get();

                    foreach ($recipients as $recipient) {
                        Notification::make()
                            ->title('Invoice generated')
                            ->body(auth()->user()->name.' generated an invoice for '.$record->serial)
                            ->icon('heroicon-o-check-badge')
                            ->success()
                            ->actions([
                                ActionsAction::make('View')
                                    ->url(InvoiceResource::getUrl('view', ['record' => $invoice->id]))
                                    ->markAsRead(),
                            ])
                            ->sendToDatabase($recipient);
                    }
                }),
        ];
    }
}
