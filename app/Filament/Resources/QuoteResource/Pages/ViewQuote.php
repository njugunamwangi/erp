<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use Filament\Actions;
use Filament\Actions\Action;
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
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-on-square-stack')
                    ->color('success')
                    ->url(URL::signedRoute('quote.download', [$this->record->id]), true)
                    ->openUrlInNewTab()
        ];
    }
}
