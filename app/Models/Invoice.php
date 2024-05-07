<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Dcblogdev\FindAndReplaceJson\FindAndReplaceJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Facades\Invoice as FacadesInvoice;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'json',
            'status' => InvoiceStatus::class,
            'series' => InvoiceSeries::class,
            'subtotal' => Money::class,
            'total' => Money::class,
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function stks(): HasMany
    {
        return $this->hasMany(MpesaSTK::class);
    }

    public function payment(): HasOne
    {
         return $this->hasOne(Payment::class);
    }

    public function savePdf()
    {
        $customer = new Buyer([
            'name' => $this->user->name,
            'custom_fields' => [
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ],
        ]);

        $items = [];

        foreach ($this->items as $item) {
            $items[] = (new InvoiceItem())
                ->title($item['description'])
                ->pricePerUnit($item['unit_price'])
                ->subTotalPrice($item['unit_price'] * $item['quantity'])
                ->quantity($item['quantity']);
        }

        FacadesInvoice::make()
            ->buyer($customer)
            ->status($this->status->name)
            ->taxRate($this->taxes)
            ->filename($this->serial)
            ->template('invoice')
            ->series($this->series->name)
            ->sequence($this->serial_number)
            ->delimiter('-')
            ->addItems($items)
            ->currencyCode($this->currency->abbr)
            ->currencySymbol($this->currency->symbol)
            ->currencyDecimals($this->currency->precision)
            ->currencyDecimalPoint($this->currency->decimal_mark)
            ->currencyThousandsSeparator($this->currency->thousands_separator)
            ->currencyFormat($this->currency->symbol_first == true ? $this->currency->symbol.' '.'{VALUE}' : '{VALUE}'.' '.$this->currency->symbol)
            ->currencyFraction($this->currency->subunit_name)
            ->save('invoices');
    }

    public function convertCurrency($data)
    {
        $api = Profile::find(1)->exchange_rate_api;

        $rates = Http::get('https://v6.exchangerate-api.com/v6/'. $api .'/latest/'.$this->currency->abbr)->json()['conversion_rates'];

        $convertTo = Currency::find($data['currency_id'])->abbr;

        $items = [];

        foreach($this->items as $item) {
            $payload = json_encode($item, true);

            $replaces = ['unit_price' => $item['unit_price'] * $rates[$convertTo]];

            $runner = new FindAndReplaceJson();

            $updatedItem = json_decode($runner->replace($payload, $replaces));

            $items[] = $updatedItem;
        }

        $exchangeRateProvider = new ConfigurableProvider();
        $exchangeRateProvider->setExchangeRate($this->currency->abbr, $convertTo, $rates[$convertTo]);
        $converter = new CurrencyConverter($exchangeRateProvider);

        $this->update([
            'currency_id' => $data['currency_id'],
            'subtotal' => $converter->convert( moneyContainer: $this->subtotal, currency: $convertTo, roundingMode: RoundingMode::UP),
            'total' => $converter->convert( moneyContainer: $this->total, currency: $convertTo, roundingMode: RoundingMode::UP),
            'items' => $items,
        ]);
    }
}
