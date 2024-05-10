<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Mail\SendQuote;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['serial_number'] = (Quote::max('serial_number') ?? 0) + 1;
        $data['serial'] = $data['series'].'-'.str_pad($data['serial_number'], 5, '0', STR_PAD_LEFT);
        if (! empty($data['task_id'])) {
            $data['vertical_id'] = Task::where('id', $data['task_id'])->first()->vertical_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $quote = $this->getRecord();

        if ($quote->mail) {

            $quote->savePdf();

            Mail::to($quote->user->email)->send(new SendQuote($quote));

            $recipients = User::role(Role::ADMIN)->get();

            foreach($recipients as $recipient) {
                Notification::make()
                    ->warning()
                    ->icon('heroicon-o-bolt')
                    ->title('Quote mailed')
                    ->body('Quote mailed to ' . $quote->user->name)
                    ->actions([
                        Action::make('view')
                            ->markAsRead()
                            ->url(QuoteResource::getUrl('view', ['record' => $quote->id]))
                            ->color('warning'),
                    ])
                    ->sendToDatabase($recipient);
            }
        }
    }
}
