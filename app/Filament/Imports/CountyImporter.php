<?php

namespace App\Filament\Imports;

use App\Models\County;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CountyImporter extends Importer
{
    protected static ?string $model = County::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('county')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('county_code')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?County
    {
        // return County::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new County();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your county import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
