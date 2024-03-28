<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Invoice;

class DownloadQuoteController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Quote $record)
    {
        $customer = new Buyer([
            'name'          => $record->user->name,
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
            ->addItems($items);

        return $invoice->download();
    }
}