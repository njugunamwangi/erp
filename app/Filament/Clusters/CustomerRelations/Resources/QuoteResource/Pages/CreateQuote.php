<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Mail\SendQuote;
use App\Models\Quote;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

            $name = 'invoice_'.$quote->series->name.'_'.str_pad($quote->serial_number, 5, '0', STR_PAD_LEFT).'.pdf';

            Storage::disk('quotes')->delete($name);
        }
    }
}
