<?php

namespace App\Filament\Resources\QuoteResource\Widgets;

use App\Models\Profile;
use App\Models\Quote;
use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class QuoteOverviewStats extends BaseWidget
{
    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Quotes by Numbers', Quote::all()->count())
                ->color('success')
                ->description('Number of quotes generated')
                ->descriptionIcon('heroicon-o-calculator'),
            Stat::make('Quotes Total amount', self::totals())
                ->color('primary')
                ->description('Exclusive of taxes')
                ->descriptionIcon('heroicon-o-banknotes'),
        ];
    }

    public function totals() {
        $api = Profile::find(1)->exchange_rate_api;

        $baseCurrency = Profile::find(1)->currency->abbr;

        $quotes = Quote::all();

        $sum = Money::of(0, $baseCurrency);

        $exchangeRateProvider = new ConfigurableProvider();

        foreach($quotes as $quote) {
            $rates = Http::get('https://v6.exchangerate-api.com/v6/'. $api .'/latest/'.$quote->currency->abbr)->json()['conversion_rates'];

            $exchangeRateProvider->setExchangeRate($quote->currency->abbr, $baseCurrency, $rates[$baseCurrency]);

            $converter = new CurrencyConverter($exchangeRateProvider);

            $amount = $converter->convert( moneyContainer: $quote->subtotal, currency: $baseCurrency, roundingMode: RoundingMode::UP);

            $sum = $sum->plus($amount);
        }

        return $sum;
    }
}
