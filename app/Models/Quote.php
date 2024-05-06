<?php

namespace App\Models;

use App\Casts\Money;
use App\Enums\QuoteSeries;
use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Dcblogdev\FindAndReplaceJson\FindAndReplaceJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Http;

class Quote extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'json',
            'series' => QuoteSeries::class,
            'total' => Money::class,
            'subtotal' => Money::class,
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
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
