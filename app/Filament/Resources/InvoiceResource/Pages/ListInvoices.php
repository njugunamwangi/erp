<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Widgets\InvoiceStatsOverview;
use App\InvoiceStatus;
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
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array {
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
            ->modifyQueryUsing(fn($query) => $query->where('status', '=', InvoiceStatus::Paid));

        $tabs['unpaid'] = Tab::make('Unpaid')
            ->badge(Invoice::where('status', '=', InvoiceStatus::Unpaid)->count())
            ->modifyQueryUsing(fn($query) => $query->where('status', '=', InvoiceStatus::Unpaid));

        return $tabs;
    }
}
