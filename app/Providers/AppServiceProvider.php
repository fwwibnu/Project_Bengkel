<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Redirect setelah login sesuai peran
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    public function boot(): void
    {
        // Nama bulan/hari Bahasa Indonesia untuk translatedFormat()
        Carbon::setLocale(config('app.locale'));

        // Hak akses halaman non-resource (dicek di server oleh canAccess() tiap halaman dan di controller)
        Gate::define('lihat-dashboard', fn (User $user) => $user->isAdmin() || $user->isPemilik());
        Gate::define('lihat-laporan', fn (User $user) => $user->isAdmin() || $user->isPemilik());
        Gate::define('proses-pembayaran', fn (User $user) => $user->isKasir());
        Gate::define('lihat-riwayat-servis', fn (User $user) => $user->isKasir() || $user->isAdmin());
        Gate::define('akses-wo-saya', fn (User $user) => $user->isMekanik());

        // Default tabel di semua halaman
        Table::configureUsing(function (Table $table): void {
            $table
                ->paginated([10, 25, 50])
                ->defaultPaginationPageOption(10);
        });
    }
}
