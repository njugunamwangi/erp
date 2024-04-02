<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\InvoiceStatus;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
                ->openUrlInNewTab(),
            Action::make('markPaid')
                ->label('Mark as Paid')
                ->visible(fn($record) => $record->status != InvoiceStatus::Paid)
                ->color('warning')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalDescription(fn($record) => 'Are you sure you want to mark ' . $record->serial . ' as paid?')
                ->modalSubmitActionLabel('Mark as Paid')
                ->action(function($record) {
                    $record->status = InvoiceStatus::Paid;
                    $record->save();

                    $recipients = User::role(Role::ADMIN)->get();

                    foreach($recipients as $recipient) {
                        Notification::make()
                            ->title('Invoice paid')
                            ->body('Invoice has been paid')
                            ->icon('heroicon-o-check-badge')
                            ->color('success')
                            ->sendToDatabase($recipient);
                    }
                }),
        ];
    }
}
