<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\QuoteResource\Pages;

use App\Filament\Clusters\CustomerRelations\Resources\QuoteResource;
use App\Mail\SendQuote;
use App\Models\Role;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

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
