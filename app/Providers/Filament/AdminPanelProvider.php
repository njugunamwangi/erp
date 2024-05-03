<?php

namespace App\Providers\Filament;

use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Resources\CountyResource;
use App\Filament\Resources\CustomFieldResource;
use App\Filament\Resources\VerticalResource;
use Awcodes\Curator\CuratorPlugin;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(env('ADMIN_SUBDOMAIN'))
            ->login()
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugins([
                FilamentSpatieRolesPermissionsPlugin::make(),
                FilamentFullCalendarPlugin::make(),
                BreezyCore::make()
                    ->avatarUploadComponent(fn($fileUpload) => $fileUpload->disableLabel())
                    // ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('profile-photos'))
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'profile' // Sets the slug for the profile page (default = 'my-profile')
                    ),
                CuratorPlugin::make()
                    ->label('Media')
                    ->pluralLabel('Media')
                    ->navigationIcon('heroicon-o-photo')
                    ->navigationGroup('Content')
                    ->navigationSort(3),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Counties')
                    ->url(fn (): string => CountyResource::getUrl())
                    ->icon('heroicon-o-globe-europe-africa'),
                MenuItem::make()
                    ->label('Custom Fields')
                    ->url(fn (): string => CustomFieldResource::getUrl())
                    ->icon('heroicon-o-viewfinder-circle'),
                MenuItem::make()
                    ->label('Verticals')
                    ->url(fn (): string => VerticalResource::getUrl())
                    ->icon('heroicon-o-chart-bar'),
            ])
            ->resources([
                config('filament-logger.activity_resource'),
            ])
            ->collapsibleNavigationGroups(true)
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Accounting & Finance')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Asset Management')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Commerce')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Content')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Customer Relations')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Settings')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('User Management')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Roles and Permissions')
                    ->collapsed(),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('1s')
            ->maxContentWidth(MaxWidth::Full)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }

    public function register(): void
    {
        parent::register();
        FilamentView::registerRenderHook('panels::body.end', fn (): string => Blade::render("@vite('resources/js/app.js')"));
    }
}
