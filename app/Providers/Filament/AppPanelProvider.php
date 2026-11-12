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
 
            // Identitas bengkel: logo + nama di sidebar, topbar HP, dan halaman login
            ->brandName(config('bengkel.nama'))
            ->brandLogo(fn (): View => view('filament.brand-logo'))
            ->brandLogoHeight('2.5rem')
 
            // Klik logo → halaman awal sesuai peran
            ->homeUrl(fn (): string => RoleHome::url(auth()->user()))
 
            // Tema "Gading & Perunggu": aksen perunggu #8C5A2B (tombol = primary-600,
            // kontras teks putih ±6:1), netral abu hangat (Stone).
            ->colors([
                'primary' => [
                    50 => '250, 245, 238',
                    100 => '243, 232, 217',
                    200 => '230, 208, 180',
                    300 => '214, 178, 138',
                    400 => '190, 140, 92',
                    500 => '166, 110, 60',
                    600 => '140, 90, 43',
                    700 => '115, 72, 31',
                    800 => '92, 58, 27',
                    900 => '66, 42, 20',
                    950 => '40, 25, 12',
                ],
                'gray' => Color::Stone,       // netral hangat
                'success' => Color::Green,    // Tersedia, Selesai, berhasil
                'warning' => Color::Amber,    // Sibuk, Sedang Dikerjakan, stok menipis
                'danger' => Color::Red,       // stok habis, hapus, gagal
                'info' => Color::Sky,         // status Baru, peran Mekanik
                'teal' => Color::Teal,        // badge peran Kasir
                'purple' => Color::Purple,    // badge peran Pemilik
            ])
 
            ->font('Inter')
 
            // Tema kustom (Tailwind v3): warna, kaca, kartu, animasi
            ->viteTheme('resources/css/filament/app/theme.css')
 
            // Tampilan terang saja
            ->darkMode(false)
 
            // Sidebar ramping, bisa diciutkan jadi ikon saja (pilihan diingat browser)
            ->sidebarWidth('16rem')
            ->sidebarCollapsibleOnDesktop()
 
            // Pindah halaman tanpa reload (terasa seperti aplikasi).
            // Halaman nota & cetak laporan punya tampilan cetak sendiri, jadi dibuka normal.
            ->spa()
            ->spaUrlExceptions([
                '*/nota/*',
                '*/laporan/cetak*',
            ])
 
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
 
            // Panel brand di sisi kiri halaman login (layar lebar)
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): View => view('filament.login-brand'),
            )
 
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
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
 