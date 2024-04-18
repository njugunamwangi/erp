<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\Task;
use Filament\Resources\Pages\CreateRecord;

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
        if($data['task_id']) {
            $data['vertical_id'] = Task::where('id', $data['task_id'])->first()->vertical_id;
        }

        return $data;
    }
}
