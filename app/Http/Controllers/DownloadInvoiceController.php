<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Profile;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
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

        $profile = Profile::find(1);

        $bank = $record->account ?? Account::where('enabled', true)->first();

        $seller = new Party([
            'name' => $profile->name,
            'phone' => $profile->phone,
            'email' => $profile->email,
            'custom_fields' => [
                'SWIFT' => $bank?->bic_swift_code,
                'Bank' => $bank?->bank_name,
                'Bank A/c No.' => $bank?->number,
            ],
        ]);

        $items = [];

        foreach ($record->items as $item) {
            $items[] = (new InvoiceItem)
                ->title($item['description'])
                ->pricePerUnit($item['unit_price'])
                ->subTotalPrice($item['unit_price'] * $item['quantity'])
                ->quantity($item['quantity']);
        }

        $invoice = InvoicesInvoice::make()
            ->buyer($customer)
            ->seller($seller)
            ->taxRate($record->taxes)
            ->status($record->status->name)
            ->filename($record->serial)
            ->template('invoice')
            ->series($record->series->name)
            ->sequence($record->serial_number)
            ->logo(empty($profile->media_id) ? '' : storage_path('/app/public/'.$profile->media->path))
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
