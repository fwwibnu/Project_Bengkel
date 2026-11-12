<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Filament\Pages\Pembayaran;
use App\Filament\Pages\WorkOrderSaya;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** Aksi di halaman Filament memanggil Service dengan benar. */
class PanelActionsTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    public function test_mekanik_mencatat_sparepart_dari_halaman_work_order_saya(): void
    {
        [$order, $mechanic] = $this->workingOrder();
        $sparepart = $this->sparepart(stock: 5);

        $this->actingAs($mechanic->user);

        Livewire::test(WorkOrderSaya::class)
            ->set('wo', $order->getKey())
            ->assertSee($order->number)
            ->callAction('catatSparepart', data: ['sparepart_id' => $sparepart->getKey(), 'qty' => 2], arguments: ['wo' => $order->getKey()])
            ->assertHasNoActionErrors();

        $this->assertSame(3, $sparepart->fresh()->stock);
    }

    public function test_kasir_membayar_dari_halaman_pembayaran(): void
    {
        [$order] = $this->finishedOrder();

        $this->actingAs($this->kasir());

        Livewire::test(Pembayaran::class)
            ->assertCanSeeTableRecords([$order])
            ->callTableAction('bayar', $order, data: ['service_fee' => 60_000, 'method' => 'tunai'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(WorkOrderStatus::Dibayar, $order->fresh()->status);
        $this->assertSame(60_000, Payment::first()->grand_total);
    }
}
