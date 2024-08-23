<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Mail\SendInvoice;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterSave(): void
    {
        $invoice = $this->getRecord();

        if ($invoice->mail) {

            $invoice->savePdf();

            Mail::to($invoice->user->email)->send(new SendInvoice($invoice));

            $recipients = User::role(Role::ADMIN)->get();

            foreach ($recipients as $recipient) {
                Notification::make()
                    ->warning()
                    ->icon('heroicon-o-bolt')
                    ->title('Invoice mailed')
                    ->body('Invoice mailed to '.$invoice->user->name)
                    ->actions([
                        Action::make('view')
                            ->markAsRead()
                            ->url(InvoiceResource::getUrl('view', ['record' => $invoice->id]))
                            ->color('warning'),
                    ])
                    ->sendToDatabase($recipient);
            }

            $name = 'invoice_'.$invoice->series->name.'_'.str_pad($invoice->serial_number, 5, '0', STR_PAD_LEFT).'.pdf';

            Storage::disk('invoices')->delete($name);
        }
    }
}
