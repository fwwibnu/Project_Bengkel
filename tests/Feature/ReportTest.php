<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** F2/F10: angka dashboard dan laporan konsisten dengan transaksi. */
class ReportTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    public function test_sirkulasi_stok_menghitung_masuk_keluar_dan_batal(): void
    {
        $admin = $this->admin();
        $sparepart = app(StockService::class)->createSparepart([
            'name' => 'Filter oli', 'stock' => 10, 'min_stock' => 2, 'purchase_price' => 25_000, 'selling_price' => 35_000,
        ], $admin);

        [$order, $mechanic] = $this->workingOrder();
        $item = app(StockService::class)->recordUsage($order, $sparepart->getKey(), 3, $mechanic->user);
        app(StockService::class)->removeItem($item, $mechanic->user);
        app(StockService::class)->recordUsage($order, $sparepart->getKey(), 2, $mechanic->user);

        $result = app(ReportService::class)->stockCirculation(now()->startOfMonth(), now());
        $row = $result['rows']->firstWhere('code', $sparepart->code);

        $this->assertSame(0, $row['awal']);
        $this->assertSame(10, $row['masuk']);
        $this->assertSame(2, $row['keluar']);   // 3 keluar − 3 batal + 2 keluar
        $this->assertSame(8, $row['akhir']);
        $this->assertSame(8, $sparepart->fresh()->stock);
    }

    public function test_ringkasan_pendapatan_dan_tren_6_bulan(): void
    {
        [$order] = $this->finishedOrder($this->sparepart(stock: 5, price: 20_000), 1);
        app(PaymentService::class)->pay($order, 80_000, 'non_tunai', $this->kasir());

        $report = app(ReportService::class);
        $summary = $report->revenueSummary(now()->startOfMonth(), now());

        $this->assertSame(1, $summary['count']);
        $this->assertSame(100_000, $summary['total']);
        $this->assertSame(80_000, $summary['fee']);
        $this->assertSame(20_000, $summary['parts']);

        $trend = $report->monthlyTrend(6);
        $this->assertCount(6, $trend['labels']);
        $this->assertSame(100_000, end($trend['revenue']));
        $this->assertSame(1, end($trend['services']));

        $stats = $report->dashboardStats();
        $this->assertSame(4, $stats['units']);
    }
}
