<?php

namespace App\Services;

use App\Enums\MechanicStatus;
use App\Enums\StockMovementType;
use App\Enums\WorkOrderStatus;
use App\Models\Mechanic;
use App\Models\Payment;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\WorkOrder;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data agregat untuk dashboard dan laporan.
 * Pengelompokan per hari/bulan dilakukan di PHP supaya sama persis
 * di MySQL (Laragon) maupun SQLite (testing), dan mengikuti zona waktu aplikasi.
 */
class ReportService
{
    /* ---------------------------------------------------------------
     | Dashboard
     |---------------------------------------------------------------*/

    /** @return array{units:int, types:int, low:int, out:int, mechanics:int, tersedia:int, sibuk:int, libur:int} */
    public function dashboardStats(): array
    {
        $statusCounts = Mechanic::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'units' => (int) Sparepart::query()->sum('stock'),
            'types' => Sparepart::query()->count(),
            'low' => Sparepart::query()->lowStock()->count(),
            'out' => Sparepart::query()->where('stock', '<=', 0)->count(),
            'mechanics' => (int) $statusCounts->sum(),
            'tersedia' => (int) ($statusCounts[MechanicStatus::Tersedia->value] ?? 0),
            'sibuk' => (int) ($statusCounts[MechanicStatus::Sibuk->value] ?? 0),
            'libur' => (int) ($statusCounts[MechanicStatus::Libur->value] ?? 0),
        ];
    }

    /**
     * Tren pendapatan (dari pembayaran) dan jumlah servis selesai per bulan.
     *
     * @return array{labels: list<string>, revenue: list<int>, services: list<int>}
     */
    public function monthlyTrend(int $months = 6, ?CarbonInterface $now = null): array
    {
        $now = Carbon::parse($now ?? now());
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);
        $end = $now->copy()->endOfMonth();

        $payments = Payment::query()
            ->whereBetween('paid_at', [$start, $end])
            ->get(['paid_at', 'grand_total'])
            ->groupBy(fn (Payment $p) => $p->paid_at->format('Y-m'));

        $finished = WorkOrder::query()
            ->whereNotNull('finished_at')
            ->whereBetween('finished_at', [$start, $end])
            ->get(['finished_at'])
            ->groupBy(fn (WorkOrder $wo) => $wo->finished_at->format('Y-m'));

        $labels = $revenue = $services = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M Y');
            $revenue[] = (int) ($payments->get($key)?->sum('grand_total') ?? 0);
            $services[] = (int) ($finished->get($key)?->count() ?? 0);
        }

        return compact('labels', 'revenue', 'services');
    }

    /**
     * Jumlah WO selesai per mekanik pada bulan berjalan (urut terbanyak).
     *
     * @return Collection<int, array{name:string, status:MechanicStatus, total:int}>
     */
    public function mechanicPerformance(?CarbonInterface $now = null): Collection
    {
        $now = Carbon::parse($now ?? now());

        return Mechanic::query()
            ->withCount(['workOrders as finished_count' => fn ($q) => $q
                ->whereNotNull('finished_at')
                ->whereBetween('finished_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])])
            ->orderByDesc('finished_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Mechanic $m) => [
                'name' => $m->name,
                'status' => $m->status,
                'total' => (int) $m->finished_count,
            ]);
    }

    /* ---------------------------------------------------------------
     | Laporan pendapatan
     |---------------------------------------------------------------*/

    /** @return array{count:int, total:int, fee:int, parts:int} */
    public function revenueSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = $this->paymentsBetween($from, $to);

        return [
            'count' => $rows->count(),
            'total' => (int) $rows->sum('grand_total'),
            'fee' => (int) $rows->sum('service_fee'),
            'parts' => (int) $rows->sum('sparepart_total'),
        ];
    }

    /**
     * Pendapatan dikelompokkan per hari ('daily') atau per bulan ('monthly'), terbaru di atas.
     *
     * @return Collection<int, array{key:string, label:string, count:int, fee:int, parts:int, total:int}>
     */
    public function revenueGrouped(CarbonInterface $from, CarbonInterface $to, string $period = 'daily'): Collection
    {
        $format = $period === 'monthly' ? 'Y-m' : 'Y-m-d';

        return $this->paymentsBetween($from, $to)
            ->groupBy(fn (Payment $p) => $p->paid_at->format($format))
            ->map(function (Collection $group, string $key) use ($period) {
                $date = $period === 'monthly'
                    ? Carbon::createFromFormat('Y-m-d', $key.'-01')
                    : Carbon::createFromFormat('Y-m-d', $key);

                return [
                    'key' => $key,
                    'label' => $period === 'monthly' ? $date->translatedFormat('F Y') : $date->translatedFormat('l, d M Y'),
                    'count' => $group->count(),
                    'fee' => (int) $group->sum('service_fee'),
                    'parts' => (int) $group->sum('sparepart_total'),
                    'total' => (int) $group->sum('grand_total'),
                ];
            })
            ->sortByDesc('key')
            ->values();
    }

    /* ---------------------------------------------------------------
     | Laporan transaksi servis
     |---------------------------------------------------------------*/

    /**
     * Work Order yang dibuat dalam rentang tanggal + ringkasan per status.
     *
     * @return array{rows: Collection<int, WorkOrder>, perStatus: array<string,int>, total:int, paidTotal:int}
     */
    public function serviceRecap(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = WorkOrder::query()
            ->with(['customer', 'vehicle', 'mechanic', 'payment'])
            ->withSum('items as parts_total', 'subtotal')
            ->whereBetween('created_at', [$this->startOf($from), $this->endOf($to)])
            ->latest()
            ->get();

        $perStatus = [];
        foreach (WorkOrderStatus::cases() as $status) {
            $perStatus[$status->value] = $rows->where('status', $status)->count();
        }

        return [
            'rows' => $rows,
            'perStatus' => $perStatus,
            'total' => $rows->count(),
            'paidTotal' => (int) $rows->sum(fn (WorkOrder $wo) => $wo->payment?->grand_total ?? 0),
        ];
    }

    /* ---------------------------------------------------------------
     | Laporan sirkulasi stok
     |---------------------------------------------------------------*/

    /**
     * Per sparepart: stok awal, masuk, keluar (bersih setelah dikurangi batal), stok akhir.
     * Stok akhir periode dihitung mundur dari stok sekarang dikurangi pergerakan setelah periode.
     *
     * @return array{rows: Collection<int, array<string,mixed>>, masuk:int, keluar:int, movements: Collection<int, StockMovement>}
     */
    public function stockCirculation(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = $this->startOf($from);
        $end = $this->endOf($to);

        $after = StockMovement::query()->where('created_at', '>', $end)->get(['sparepart_id', 'type', 'qty'])->groupBy('sparepart_id');
        $inRange = StockMovement::query()->whereBetween('created_at', [$start, $end])->get(['sparepart_id', 'type', 'qty'])->groupBy('sparepart_id');

        $net = fn (?Collection $moves) => (int) ($moves?->sum(fn (StockMovement $m) => $m->type->arah() * $m->qty) ?? 0);
        $sumType = fn (?Collection $moves, StockMovementType $type) => (int) ($moves?->where('type', $type)->sum('qty') ?? 0);

        $rows = Sparepart::query()->orderBy('code')->get()->map(function (Sparepart $sp) use ($after, $inRange, $net, $sumType) {
            $moves = $inRange->get($sp->getKey());
            $akhir = $sp->stock - $net($after->get($sp->getKey()));
            $masuk = $sumType($moves, StockMovementType::Masuk);
            $keluar = $sumType($moves, StockMovementType::Keluar) - $sumType($moves, StockMovementType::Batal);
            $awal = $akhir - $masuk + $keluar;

            return [
                'code' => $sp->code,
                'name' => $sp->name,
                'awal' => $awal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'akhir' => $akhir,
                'min_stock' => $sp->min_stock,
            ];
        });

        $movements = StockMovement::query()
            ->with(['sparepart', 'user', 'workOrder.mechanic'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->limit(50)
            ->get();

        return [
            'rows' => $rows,
            'masuk' => (int) $rows->sum('masuk'),
            'keluar' => (int) $rows->sum('keluar'),
            'movements' => $movements,
        ];
    }

    /* ---------------------------------------------------------------*/

    /** @return Collection<int, Payment> */
    protected function paymentsBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Payment::query()
            ->whereBetween('paid_at', [$this->startOf($from), $this->endOf($to)])
            ->get(['id', 'paid_at', 'service_fee', 'sparepart_total', 'grand_total']);
    }

    protected function startOf(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date)->startOfDay();
    }

    protected function endOf(CarbonInterface $date): Carbon
    {
        return Carbon::parse($date)->endOfDay();
    }
}
