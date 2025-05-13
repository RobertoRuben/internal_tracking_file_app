<?php

namespace App\Providers\Filament;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Volt\Volt;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Tus páginas
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Tus widgets
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                // Tus plugins
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationItems([
                // Tus items de navegación
            ])
            ->databaseNotifications()
            ->renderHook(
                'panels::body.end',
                fn (): string => "
                    <script>
                        document.addEventListener('livewire:init', () => {
                            Livewire.on('open-browser-tab', ({ url }) => {
                                window.open(url, '_blank');
                            });
                            
                            Livewire.on('eval-html', ({ html }) => {
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = html;
                                document.body.appendChild(tempDiv);
                                
                                // Ejecutar cualquier script en el HTML
                                const scripts = tempDiv.getElementsByTagName('script');
                                for (let i = 0; i < scripts.length; i++) {
                                    eval(scripts[i].innerText);
                                }
                                
                                // Opcionalmente, eliminar el div después de ejecutar
                                setTimeout(() => {
                                    document.body.removeChild(tempDiv);
                                }, 1000);
                            });
                        });
                    </script>
                "
            );
    }
}
