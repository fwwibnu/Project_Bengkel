<?php

namespace Tests\Feature;

use App\Enums\MechanicStatus;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\MechanicResource\Pages\ListMechanics;
use App\Filament\Resources\SparepartResource\Pages\ListSpareparts;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use App\Filament\Resources\WorkOrderResource\Pages\ListWorkOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** Halaman data (tampilan kartu): data tampil, pencarian, dan tab filter bekerja. */
class DataPagesTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    public function test_daftar_work_order_tampil_dan_bisa_dicari_serta_difilter_status(): void
    {
        $baru = $this->newWorkOrder();
        [$dikerjakan] = $this->workingOrder();

        $this->actingAs($this->admin());

        Livewire::test(ListWorkOrders::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$baru, $dikerjakan])
            ->assertSee($baru->vehicle->plate_number)
            ->searchTable($baru->vehicle->plate_number)
            ->assertCanSeeTableRecords([$baru])
            ->assertCanNotSeeTableRecords([$dikerjakan]);

        Livewire::test(ListWorkOrders::class)
            ->set('activeTab', 'dikerjakan')
            ->assertCanSeeTableRecords([$dikerjakan])
            ->assertCanNotSeeTableRecords([$baru]);
    }

    public function test_daftar_sparepart_tampil_dan_tab_perlu_dibeli_hanya_berisi_stok_menipis(): void
    {
        $aman = $this->sparepart(stock: 50);
        $habis = $this->sparepart(stock: 0);

        $this->actingAs($this->admin());

        Livewire::test(ListSpareparts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$aman, $habis])
            ->assertSee('Habis')
            ->set('activeTab', 'perlu')
            ->assertCanSeeTableRecords([$habis])
            ->assertCanNotSeeTableRecords([$aman]);
    }

    public function test_daftar_pelanggan_bisa_dicari_lewat_plat_kendaraan(): void
    {
        $a = $this->newWorkOrder();
        $b = $this->newWorkOrder();

        $this->actingAs($this->admin());

        Livewire::test(ListCustomers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$a->customer, $b->customer])
            ->assertSee('Di bengkel')
            ->searchTable($a->vehicle->plate_number)
            ->assertCanSeeTableRecords([$a->customer])
            ->assertCanNotSeeTableRecords([$b->customer]);
    }

    public function test_daftar_kendaraan_tampil_dengan_tab_jenis(): void
    {
        $order = $this->newWorkOrder();
        $vehicle = $order->vehicle;

        $this->actingAs($this->admin());

        Livewire::test(ListVehicles::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$vehicle])
            ->assertSee($vehicle->plate_number)
            ->set('activeTab', $vehicle->type->value)
            ->assertCanSeeTableRecords([$vehicle]);
    }

    public function test_daftar_mekanik_menampilkan_wo_yang_dipegang_dan_tab_status(): void
    {
        [$order, $sibuk] = $this->workingOrder();
        $libur = $this->mechanic(['status' => MechanicStatus::Libur->value]);

        $this->actingAs($this->admin());

        Livewire::test(ListMechanics::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$sibuk, $libur])
            ->assertSee($order->number)
            ->set('activeTab', MechanicStatus::Libur->value)
            ->assertCanSeeTableRecords([$libur])
            ->assertCanNotSeeTableRecords([$sibuk]);
    }
}
