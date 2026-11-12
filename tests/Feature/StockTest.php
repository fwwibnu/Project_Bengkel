<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\BusinessRuleException;
use App\Models\StockMovement;
use App\Models\WorkOrderItem;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** F3/F7: stok berkurang saat dicatat, harga terkunci, stok tidak boleh negatif, Tambah Stok. */
class StockTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    protected function stock(): StockService
    {
        return app(StockService::class);
    }

    public function test_catat_sparepart_mengurangi_stok_dan_mengunci_harga(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 10, price: 55_000);

        $item = $this->stock()->recordUsage($order, $sparepart->getKey(), 3, $mechanic->user);

        $this->assertSame(7, $sparepart->fresh()->stock);
        $this->assertSame(55_000, $item->unit_price);
        $this->assertSame(165_000, $item->subtotal);

        // Harga master naik → harga di WO tetap
        $sparepart->update(['selling_price' => 70_000]);
        $this->assertSame(55_000, $item->fresh()->unit_price);

        $this->assertDatabaseHas('stock_movements', [
            'sparepart_id' => $sparepart->getKey(),
            'type' => StockMovementType::Keluar->value,
            'qty' => 3,
            'work_order_id' => $order->getKey(),
        ]);
    }

    public function test_stok_kurang_ditolak_dan_tidak_ada_yang_berubah(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 2);

        try {
            $this->stock()->recordUsage($order, $sparepart->getKey(), 3, $mechanic->user);
            $this->fail('Seharusnya ditolak');
        } catch (BusinessRuleException $e) {
            $this->assertStringContainsString('Stok tidak cukup', $e->getMessage());
        }

        $this->assertSame(2, $sparepart->fresh()->stock);
        $this->assertSame(0, WorkOrderItem::count());
        $this->assertSame(0, StockMovement::where('type', StockMovementType::Keluar->value)->count());
    }

    public function test_sparepart_sama_digabung_ke_satu_baris(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 10);

        $this->stock()->recordUsage($order, $sparepart->getKey(), 1, $mechanic->user);
        $this->stock()->recordUsage($order, $sparepart->getKey(), 2, $mechanic->user);

        $this->assertSame(1, $order->items()->count());
        $this->assertSame(3, $order->items()->first()->qty);
        $this->assertSame(7, $sparepart->fresh()->stock);
    }

    public function test_catat_sparepart_hanya_saat_sedang_dikerjakan(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic); // status Baru

        $this->expectException(BusinessRuleException::class);
        $this->stock()->recordUsage($order, $this->sparepart()->getKey(), 1, $mechanic->user);
    }

    public function test_hapus_item_saat_dikerjakan_mengembalikan_stok(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 10);
        $item = $this->stock()->recordUsage($order, $sparepart->getKey(), 4, $mechanic->user);

        $this->stock()->removeItem($item, $mechanic->user);

        $this->assertSame(10, $sparepart->fresh()->stock);
        $this->assertModelMissing($item);
        $this->assertDatabaseHas('stock_movements', [
            'sparepart_id' => $sparepart->getKey(),
            'type' => StockMovementType::Batal->value,
            'qty' => 4,
        ]);
    }

    public function test_hapus_item_setelah_selesai_ditolak(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 10);
        $item = $this->stock()->recordUsage($order, $sparepart->getKey(), 1, $mechanic->user);
        app(WorkOrderService::class)->finish($order, $mechanic->user, 'Selesai');

        try {
            $this->stock()->removeItem($item, $mechanic->user);
            $this->fail('Seharusnya ditolak');
        } catch (BusinessRuleException) {
            $this->assertModelExists($item);
            $this->assertSame(9, $sparepart->fresh()->stock);
        }
    }

    public function test_tambah_stok_tercatat_sebagai_stok_masuk(): void
    {
        $sparepart = $this->sparepart(stock: 1);

        $this->stock()->addStock($sparepart, 15, 'Pembelian dari supplier', $this->admin());

        $this->assertSame(16, $sparepart->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'sparepart_id' => $sparepart->getKey(),
            'type' => StockMovementType::Masuk->value,
            'qty' => 15,
            'note' => 'Pembelian dari supplier',
        ]);
    }

    public function test_hanya_admin_yang_bisa_tambah_stok(): void
    {
        $sparepart = $this->sparepart(stock: 1);

        $this->expectException(AuthorizationException::class);
        $this->stock()->addStock($sparepart, 5, null, $this->pemilik());
    }

    public function test_sparepart_baru_dapat_kode_otomatis_dan_stok_awal_tercatat(): void
    {
        $sparepart = $this->stock()->createSparepart([
            'code' => null,
            'name' => 'Busi iridium',
            'stock' => 12,
            'min_stock' => 4,
            'purchase_price' => 60_000,
            'selling_price' => 85_000,
        ], $this->admin());

        $this->assertSame('SP-001', $sparepart->code);
        $this->assertSame(12, $sparepart->fresh()->stock);
        $this->assertSame(1, $sparepart->movements()->where('type', StockMovementType::Masuk->value)->count());
    }
}
