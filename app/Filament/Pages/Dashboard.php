<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LowStockTable;
use App\Filament\Widgets\MechanicPerformanceChart;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use App\Support\Format;
use App\Support\RoleHome;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Gate;

/**
 * Dashboard untuk Admin & Pemilik. Peran lain yang membuka /app
 * langsung diarahkan ke halaman awalnya (Mekanik → Work Order Saya, Kasir → Pembayaran).
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function shouldRegisterNavigation(): bool
    {
        return Gate::allows('lihat-dashboard');
    }

    public function mount(): void
    {
        if (! Gate::allows('lihat-dashboard')) {
            $this->redirect(RoleHome::url(auth()->user()));
        }
    }

    public function getSubheading(): ?string
    {
        return Format::hariTanggal(now());
    }

    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RevenueChart::class,
            MechanicPerformanceChart::class,
            LowStockTable::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return ['default' => 1, 'lg' => 3];
    }
}
