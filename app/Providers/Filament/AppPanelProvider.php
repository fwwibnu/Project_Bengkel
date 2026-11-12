<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Support\RoleHome;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')

            // Halaman login kustom: username (atau email) + password
            ->login(Login::class)

            // Identitas bengkel di sidebar dan judul tab browser (config/bengkel.php)
            ->brandName(config('bengkel.nama'))

            // Klik logo → halaman awal sesuai peran
            ->homeUrl(fn (): string => RoleHome::url(auth()->user()))

            // Palet warna (UI_GUIDE). Primary digeser satu tingkat supaya tombol = #C2410C
            // (kontras teks putih cukup), hover = #EA580C.
            ->colors([
                'primary' => [
                    50 => '255, 247, 237',
                    100 => '255, 237, 213',
                    200 => '254, 215, 170',
                    300 => '253, 186, 116',
                    400 => '249, 115, 22',
                    500 => '234, 88, 12',
                    600 => '194, 65, 12',
                    700 => '154, 52, 18',
                    800 => '124, 45, 18',
                    900 => '67, 20, 7',
                    950 => '45, 13, 4',
                ],
                'gray' => Color::Slate,       // warna netral
                'success' => Color::Green,    // Tersedia, Selesai, berhasil
                'warning' => Color::Amber,    // Sibuk, Sedang Dikerjakan, stok menipis
                'danger' => Color::Red,       // stok habis, hapus, gagal
                'info' => Color::Sky,         // status Baru, peran Mekanik
                'teal' => Color::Teal,        // badge peran Kasir
                'purple' => Color::Purple,    // badge peran Pemilik
            ])

            // Satu keluarga font untuk seluruh panel
            ->font('Plus Jakarta Sans')

            // Tema kustom (Tailwind v3): sidebar gelap, kartu, badge, tabel
            ->viteTheme('resources/css/filament/app/theme.css')

            // Tampilan terang saja, sesuai desain yang disetujui
            ->darkMode(false)

            ->sidebarCollapsibleOnDesktop()

            // Urutan grup menu (tiap peran hanya melihat menu miliknya)
            ->navigationGroups([
                'Operasional',
                'Kasir',
                'Data Master',
                'Laporan',
            ])

            // Nama + badge peran pengguna di kanan atas, sebelum avatar
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('filament.user-info'),
            )

            // Panel brand gelap di sisi kiri halaman login (layar lebar)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): View => view('filament.login-brand'),
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
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
            ]);
    }
}
