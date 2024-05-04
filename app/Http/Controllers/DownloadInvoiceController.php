<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Invoice as InvoicesInvoice;

class DownloadInvoiceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Invoice $record)
    {
        $customer = new Buyer([
            'name' => $record->user->name,
            'custom_fields' => [
                'email' => $record->user->email,
                'phone' => $record->user->phone,
            ],
        ]);

        $items = [];

        foreach ($record->items as $item) {
            $items[] = (new InvoiceItem())
                ->title($item['description'])
                ->pricePerUnit($item['unit_price'])
                ->subTotalPrice($item['unit_price'] * $item['quantity'])
                ->quantity($item['quantity']);
        }

        $invoice = InvoicesInvoice::make()
            ->buyer($customer)
            ->taxRate($record->taxes)
            ->status($record->status->name)
            ->filename($record->serial)
            ->template('invoice')
            ->series($record->series->name)
            ->sequence($record->serial_number)
            ->delimiter('-')
            ->currencyCode($record->currency->abbr)
            ->currencySymbol($record->currency->symbol)
            ->currencyDecimals($record->currency->precision)
            ->currencyDecimalPoint($record->currency->decimal_mark)
            ->currencyThousandsSeparator($record->currency->thousands_separator)
            ->currencyFormat($record->currency->symbol_first == true ? $record->currency->symbol.' '.'{VALUE}' : '{VALUE}'.' '.$record->currency->symbol)
            ->notes($record->notes)
            ->currencyFraction($record->currency->subunit_name)
            ->addItems($items);

        return $invoice->download();
    }
}
