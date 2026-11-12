<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MechanicResource;
use App\Filament\Resources\SparepartResource;
use App\Services\ReportService;
use App\Support\Format;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('lihat-dashboard');
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $s = app(ReportService::class)->dashboardStats();
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return [
            Stat::make('Total stok sparepart', Format::angka($s['units']).' unit')
                ->description('dari '.$s['types'].' jenis sparepart')
                ->descriptionIcon('heroicon-m-cube')
                ->color('gray'),

            Stat::make('Stok menipis', $s['low'].' item')
                ->description($s['out'] > 0 ? $s['out'].' di antaranya habis' : 'stok di bawah batas minimal')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($s['low'] > 0 ? 'warning' : 'success')
                ->url($isAdmin ? SparepartResource::getUrl('index') : null),

            Stat::make('Jumlah mekanik', $s['mechanics'].' orang')
                ->description('terdaftar aktif')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray')
                ->url($isAdmin ? MechanicResource::getUrl('index') : null),

            Stat::make('Status mekanik', $s['tersedia'].' tersedia')
                ->description($s['sibuk'].' sibuk · '.$s['libur'].' libur')
                ->descriptionIcon('heroicon-m-signal')
                ->color($s['tersedia'] > 0 ? 'success' : 'warning'),
        ];
    }
}
