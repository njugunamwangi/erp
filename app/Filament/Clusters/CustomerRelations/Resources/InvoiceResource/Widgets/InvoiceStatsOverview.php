<?php

namespace App\Filament\Clusters\CustomerRelations\Resources\InvoiceResource\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Profile;
use Brick\Math\RoundingMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Invoices by Numbers', Invoice::all()->count())
                ->color('primary')
                ->description('Number of invoices generated')
                ->descriptionIcon('heroicon-o-calculator'),
            Stat::make('Invoices Total amount', 0)
                ->color('warning')
                ->description('Exclusive of taxes')
                ->descriptionIcon('heroicon-o-banknotes'),
            Stat::make('Paid Invoices', Invoice::where('status', '=', InvoiceStatus::Paid)->count())
                ->color('success')
                ->description('Number of invoices paid')
                ->descriptionIcon('heroicon-o-check-badge'),
        ];
    }

    public function totals()
    {
        $api = Profile::find(1)->exchange_rate_api;

        $baseCurrency = Profile::find(1)->currency->abbr;

        $invoices = Invoice::all();

        $sum = Money::of(0, $baseCurrency);

        $exchangeRateProvider = new ConfigurableProvider();

        foreach ($invoices as $invoice) {
            $rates = Http::get('https://v6.exchangerate-api.com/v6/'.$api.'/latest/'.$invoice->currency->abbr)->json()['conversion_rates'];

            $exchangeRateProvider->setExchangeRate($invoice->currency->abbr, $baseCurrency, $rates[$baseCurrency]);

            $converter = new CurrencyConverter($exchangeRateProvider);

            $amount = $converter->convert(moneyContainer: $invoice->subtotal, currency: $baseCurrency, roundingMode: RoundingMode::UP);

            $sum = $sum->plus($amount);
        }

        return $sum;
    }
}
