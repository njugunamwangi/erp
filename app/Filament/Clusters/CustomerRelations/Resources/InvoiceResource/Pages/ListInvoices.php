<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource;
use App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource\Widgets\InvoiceStatsOverview;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-squares-plus'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvoiceStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs['all'] = Tab::make('All Invoices')
            ->badge(Invoice::count());

        $tabs['paid'] = Tab::make('Paid')
            ->badge(Invoice::where('status', '=', InvoiceStatus::Paid)->count())
            ->modifyQueryUsing(fn ($query) => $query->where('status', '=', InvoiceStatus::Paid));

        $tabs['unpaid'] = Tab::make('Unpaid')
            ->badge(Invoice::where('status', '=', InvoiceStatus::Unpaid)->count())
            ->modifyQueryUsing(fn ($query) => $query->where('status', '=', InvoiceStatus::Unpaid));

        return $tabs;
    }
}
