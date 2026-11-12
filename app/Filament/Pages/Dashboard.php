<?php

namespace App\Filament\Pages;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\MechanicResource;
use App\Filament\Resources\SparepartResource;
use App\Filament\Resources\WorkOrderResource;
use App\Models\Mechanic;
use App\Models\Sparepart;
use App\Models\WorkOrder;
use App\Services\ReportService;
use App\Support\Format;
use App\Support\RoleHome;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Dashboard untuk Admin & Pemilik (tampilan kustom, tanpa widget bawaan).
 * Peran lain yang membuka /app langsung diarahkan ke halaman awalnya
 * (Mekanik → Work Order Saya, Kasir → Pembayaran).
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static string $view = 'filament.pages.dashboard';

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

    /** Sapaan sesuai jam, mis. "Selamat malam, Rina." */
    public function getHeading(): string|Htmlable
    {
        $hour = now()->hour;

        $waktu = match (true) {
            $hour < 11 => 'pagi',
            $hour < 15 => 'siang',
            $hour < 18 => 'sore',
            default => 'malam',
        };

        $nama = Str::of(auth()->user()?->name ?? '')->trim()->before(' ')->toString();

        return 'Selamat '.$waktu.($nama !== '' ? ', '.$nama : '').'.';
    }

    /** Tanggal + ringkasan singkat pekerjaan hari ini. */
    public function getSubheading(): ?string
    {
        $counts = $this->workOrderCounts();
        $aktif = $counts['baru'] + $counts['dikerjakan'];

        $ringkas = $aktif > 0
            ? $aktif.' Work Order sedang berjalan'
            : 'Tidak ada Work Order yang berjalan';

        $ringkas .= $counts['selesai'] > 0
            ? ', '.$counts['selesai'].' menunggu dibayar di kasir.'
            : '.';

        return Format::hariTanggal(now()).' · '.$ringkas;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buatWorkOrder')
                ->label('Work Order baru')
                ->icon('heroicon-m-plus')
                ->url(fn (): string => WorkOrderResource::getUrl('create'))
                ->visible(fn (): bool => WorkOrderResource::canCreate()),
        ];
    }

    /** Dashboard ini tidak memakai widget bawaan Filament. */
    public function getWidgets(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $report = app(ReportService::class);
        $now = now();
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        // Pendapatan bulan berjalan
        $month = $report->revenueSummary($now->copy()->startOfMonth(), $now);

        // Grafik batang 6 bulan (bulan berjalan disorot)
        $trend = $report->monthlyTrend(6, $now);
        $max = max(1, max($trend['revenue'] ?: [0]));
        $last = count($trend['labels']) - 1;

        $bars = collect($trend['labels'])->map(fn (string $label, int $i): array => [
            'name' => Str::before($label, ' '),
            'tip' => 'Rp '.number_format($trend['revenue'][$i] / 1_000_000, 1, ',', '.').' jt · '.$trend['services'][$i].' servis',
            'height' => max(4, (int) round($trend['revenue'][$i] / $max * 100)),
            'current' => $i === $last,
            'delay' => 300 + $i * 90,
        ])->all();

        return [
            'stats' => $report->dashboardStats(),
            'counts' => $this->workOrderCounts(),
            'month' => $month,
            'monthName' => $now->translatedFormat('F'),
            'bars' => $bars,
            'mechanics' => $this->mechanicRows($now),
            'lowStock' => Sparepart::query()->lowStock()->orderBy('stock')->orderBy('name')->limit(5)->get(),
            'sparepartUrl' => $isAdmin ? SparepartResource::getUrl('index') : null,
            'mechanicUrl' => $isAdmin ? MechanicResource::getUrl('index') : null,
        ];
    }

    /** @return array{baru:int, dikerjakan:int, selesai:int} */
    protected function workOrderCounts(): array
    {
        $counts = WorkOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'baru' => (int) ($counts[WorkOrderStatus::Baru->value] ?? 0),
            'dikerjakan' => (int) ($counts[WorkOrderStatus::Dikerjakan->value] ?? 0),
            'selesai' => (int) ($counts[WorkOrderStatus::Selesai->value] ?? 0),
        ];
    }

    /**
     * Daftar mekanik untuk kartu "Mekanik hari ini": Tersedia → Sibuk → Libur.
     *
     * @return list<array{initials:string, name:string, status:MechanicStatus, note:string}>
     */
    protected function mechanicRows(CarbonInterface $now): array
    {
        $order = [
            MechanicStatus::Tersedia->value => 0,
            MechanicStatus::Sibuk->value => 1,
            MechanicStatus::Libur->value => 2,
        ];

        return Mechanic::query()
            ->with(['activeWorkOrders' => fn ($q) => $q->orderBy('created_at')])
            ->withCount(['workOrders as finished_month' => fn ($q) => $q
                ->whereNotNull('finished_at')
                ->whereBetween('finished_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])])
            ->orderBy('name')
            ->get()
            ->sortBy(fn (Mechanic $m): int => $order[$m->status->value] ?? 9)
            ->values()
            ->map(function (Mechanic $m): array {
                $working = $m->activeWorkOrders->firstWhere('status', WorkOrderStatus::Dikerjakan);
                $baru = $m->activeWorkOrders->where('status', WorkOrderStatus::Baru)->count();

                $note = match (true) {
                    $m->status === MechanicStatus::Libur => 'Libur hari ini',
                    $working !== null => 'Mengerjakan '.$working->number,
                    $baru > 0 => 'Ada '.$baru.' Work Order baru',
                    default => 'Siap menerima Work Order',
                };

                if ($m->finished_month > 0) {
                    $note .= ' · '.$m->finished_month.' selesai bulan ini';
                }

                $initials = Str::of($m->name)
                    ->explode(' ')
                    ->filter()
                    ->take(2)
                    ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                    ->join('');

                return [
                    'initials' => $initials,
                    'name' => $m->name,
                    'status' => $m->status,
                    'note' => $note,
                ];
            })
            ->all();
    }
}
