<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\OtpPasswordReset;
use App\Filament\Pages\Auth\SetNewPassword;
use App\Filament\Pages\Auth\VerifyOtp;
use App\Filament\Pages\Dashboard;
use App\Filament\Profile\EditProfile;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Assets\Css;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->spa()
            ->login(Login::class)
            ->passwordReset(OtpPasswordReset::class, SetNewPassword::class)
            ->routes(function (Panel $panel): void {
                Route::get('/password-reset/verify-otp', VerifyOtp::class)
                    ->name('auth.password-reset.verify-otp');
            })
            ->brandName('School Class Progress')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset(file_exists(public_path('storage/assets/SMK_Negeri_1_Bantul_logo (1).png')) ? 'storage/assets/SMK_Negeri_1_Bantul_logo (1).png' : 'storage/Assets/SMK_Negeri_1_Bantul_logo (1).png'))
            ->profile(EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->assets([
                Css::make('custom-filament-styles', asset('css/filament/custom.css?v=' . (file_exists(public_path('css/filament/custom.css')) ? filemtime(public_path('css/filament/custom.css')) : time()))),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
