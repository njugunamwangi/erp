<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole(Role::ADMIN);
        });

        FilamentAsset::register([
            Css::make('custom-stylesheet', public_path('invoices/css/style.css')),
            Css::make('font-stylesheet', public_path('invoices/fonts/font-awesome/css/font-awesome.min.css')),
            Css::make('bootstrap-stylesheet', public_path('invoices/css/bootstrap.min.css')),
        ]);
    }
}
