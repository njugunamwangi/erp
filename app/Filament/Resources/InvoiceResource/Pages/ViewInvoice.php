<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Actions\Action as ActionsAction;
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
                ->visible(fn ($record) => $record->status != InvoiceStatus::Paid)
                ->color('warning')
                ->icon('heroicon-o-banknotes')
                ->requiresConfirmation()
                ->modalDescription(fn ($record) => 'Are you sure you want to mark '.$record->serial.' as paid?')
                ->modalIcon('heroicon-o-banknotes')
                ->modalSubmitActionLabel('Mark as Paid')
                ->action(function ($record) {
                    $record->status = InvoiceStatus::Paid;
                    $record->save();

                    $recipients = User::role(Role::ADMIN)->get();

                    foreach ($recipients as $recipient) {
                        Notification::make()
                            ->title('Invoice paid')
                            ->body(auth()->user()->name.' marked '.$record->serial.' as paid')
                            ->icon('heroicon-o-banknotes')
                            ->warning()
                            ->actions([
                                ActionsAction::make('View')
                                    ->url(InvoiceResource::getUrl('view', ['record' => $record->id]))
                                    ->markAsRead(),
                            ])
                            ->sendToDatabase($recipient);
                    }
                }),
        ];
    }
}
