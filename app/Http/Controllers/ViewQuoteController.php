<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Invoice;

class ViewQuoteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Quote $record)
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

        $invoice = Invoice::make()
            ->buyer($customer)
            ->taxRate($record->taxes)
            ->filename($record->serial)
            ->template('quote')
            ->name('Quote')
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

        return $invoice->stream();
    }
}
