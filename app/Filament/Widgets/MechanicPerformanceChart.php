<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Gate;

class MechanicPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Performa mekanik';

    protected static ?string $description = 'Work Order selesai bulan ini';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

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
        $rows = app(ReportService::class)->mechanicPerformance();

        return [
            'datasets' => [
                [
                    'label' => 'WO selesai',
                    'data' => $rows->pluck('total')->all(),
                    'backgroundColor' => '#EA580C',
                    'borderRadius' => 6,
                    'barThickness' => 18,
                ],
            ],
            'labels' => $rows->map(fn (array $r) => $r['name'].' ('.$r['status']->getLabel().')')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
