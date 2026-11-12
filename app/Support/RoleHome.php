<?php

namespace App\Support;

use App\Enums\Role;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Pembayaran;
use App\Filament\Pages\WorkOrderSaya;
use App\Models\User;

/**
 * Halaman awal per peran (dipakai setelah login, klik logo, dan saat membuka "/"):
 * Admin & Pemilik → Dashboard, Mekanik → Work Order Saya, Kasir → Pembayaran.
 */
class RoleHome
{
    public static function url(?User $user): string
    {
        if (! $user) {
            return url('/app/login');
        }

        return match ($user->role) {
            Role::Mekanik => WorkOrderSaya::getUrl(panel: 'app'),
            Role::Kasir => Pembayaran::getUrl(panel: 'app'),
            default => Dashboard::getUrl(panel: 'app'),
        };
    }
}
