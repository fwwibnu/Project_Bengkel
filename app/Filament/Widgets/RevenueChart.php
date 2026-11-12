<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Gate;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Tren pendapatan & servis';

    protected static ?string $description = '6 bulan terakhir · pendapatan dalam juta Rupiah';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    public static function canView(): bool
    {
        return Gate::allows('lihat-dashboard');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $trend = app(ReportService::class)->monthlyTrend(6);

        return [
            'datasets' => [
                [
                    'type' => 'line',
                    'label' => 'Pendapatan (juta Rp)',
                    'data' => array_map(fn (int $v) => round($v / 1_000_000, 2), $trend['revenue']),
                    'borderColor' => '#EA580C',
                    'backgroundColor' => '#EA580C',
                    'pointBackgroundColor' => '#FFFFFF',
                    'pointBorderColor' => '#EA580C',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                    'order' => 0,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Jumlah servis',
                    'data' => $trend['services'],
                    'backgroundColor' => '#CBD5E1',
                    'borderColor' => '#CBD5E1',
                    'borderWidth' => 0,
                    'borderRadius' => 4,
                    'yAxisID' => 'y1',
                    'order' => 1,
                ],
            ],
            'labels' => $trend['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'bottom'],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Juta Rp'],
                ],
                'y1' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'beginAtZero' => true,
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => ['precision' => 0],
                    'title' => ['display' => true, 'text' => 'Servis'],
                ],
            ],
        ];
    }
}