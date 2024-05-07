<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\SendInvoice;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

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

            $recipients = User::role(Role::ADMIN)->get();

            foreach($recipients as $recipient) {
                Notification::make()
                    ->warning()
                    ->icon('heroicon-o-bolt')
                    ->title('Invoice mailed')
                    ->body('Invoice mailed to ' . $invoice->user->name)
                    ->actions([
                        Action::make('view')
                            ->markAsRead()
                            ->url(InvoiceResource::getUrl('view', ['record' => $invoice->id]))
                            ->color('warning'),
                    ])
                    ->sendToDatabase($recipient);
            }
        }
    }
}
