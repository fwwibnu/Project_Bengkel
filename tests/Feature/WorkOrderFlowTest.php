<?php

namespace Tests\Feature;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** F6/F7: buat WO, penomoran, mekanik Sibuk/Tersedia, transisi status, edit/hapus. */
class WorkOrderFlowTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    protected function service(): WorkOrderService
    {
        return app(WorkOrderService::class);
    }

    public function test_buat_wo_membuat_nomor_otomatis_dan_mekanik_jadi_sibuk(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);

        $this->assertMatchesRegularExpression('/^WO-'.now()->format('Ymd').'-001$/', $order->number);
        $this->assertSame(WorkOrderStatus::Baru, $order->fresh()->status);
        $this->assertSame(MechanicStatus::Sibuk, $mechanic->fresh()->status);

        $second = $this->newWorkOrder();
        $this->assertStringEndsWith('-002', $second->number);
    }

    public function test_mekanik_libur_atau_sibuk_tidak_bisa_dipilih(): void
    {
        $libur = $this->mechanic(['status' => MechanicStatus::Libur]);
        $sibuk = $this->mechanic();
        $this->newWorkOrder($sibuk); // jadi sibuk

        foreach ([$libur, $sibuk] as $mechanic) {
            try {
                $this->newWorkOrder($mechanic);
                $this->fail('Seharusnya ditolak');
            } catch (BusinessRuleException $e) {
                $this->assertSame('Mekanik yang dipilih sudah tidak tersedia. Pilih mekanik lain.', $e->getMessage());
            }
        }

        $this->assertSame(1, WorkOrder::count());
    }

    public function test_kendaraan_harus_milik_pelanggan_yang_dipilih(): void
    {
        $a = Vehicle::factory()->create();
        $b = Vehicle::factory()->create();

        $this->expectException(BusinessRuleException::class);

        $this->service()->create([
            'customer_id' => $a->customer_id,
            'vehicle_id' => $b->getKey(),
            'mechanic_id' => $this->mechanic()->getKey(),
            'complaint' => 'Tes',
        ], $this->admin());
    }

    public function test_hanya_admin_yang_bisa_membuat_wo(): void
    {
        $vehicle = Vehicle::factory()->create();

        foreach ([$this->kasir(), $this->pemilik(), $this->mechanic()->user] as $user) {
            try {
                $this->service()->create([
                    'customer_id' => $vehicle->customer_id,
                    'vehicle_id' => $vehicle->getKey(),
                    'mechanic_id' => $this->mechanic()->getKey(),
                    'complaint' => 'Tes',
                ], $user);
                $this->fail('Seharusnya ditolak untuk '.$user->role->value);
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, WorkOrder::count());
    }

    public function test_alur_status_lengkap_dan_mekanik_kembali_tersedia(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);

        $this->service()->start($order, $mechanic->user);
        $this->assertSame(WorkOrderStatus::Dikerjakan, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->started_at);

        $this->service()->finish($order, $mechanic->user, 'Ganti kampas rem');
        $order->refresh();

        $this->assertSame(WorkOrderStatus::Selesai, $order->status);
        $this->assertNotNull($order->finished_at);
        $this->assertSame('Ganti kampas rem', $order->work_notes);
        $this->assertSame(MechanicStatus::Tersedia, $mechanic->fresh()->status);
    }

    public function test_transisi_status_ilegal_ditolak(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);

        // Loncat: Baru → Selesai
        try {
            $this->service()->finish($order, $mechanic->user);
            $this->fail('Loncat status seharusnya ditolak');
        } catch (BusinessRuleException) {
            $this->assertSame(WorkOrderStatus::Baru, $order->fresh()->status);
        }

        // Mulai dua kali
        $this->service()->start($order, $mechanic->user);
        $this->expectException(BusinessRuleException::class);
        $this->service()->start($order, $mechanic->user);
    }

    public function test_mekanik_lain_tidak_bisa_mengerjakan_wo_orang_lain(): void
    {
        $order = $this->newWorkOrder();
        $other = $this->mechanic();

        $this->expectException(AuthorizationException::class);
        $this->service()->start($order, $other->user);
    }

    public function test_edit_dan_hapus_hanya_saat_baru(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);
        $admin = $this->admin();

        // Saat Baru: ganti mekanik → mekanik lama Tersedia, baru Sibuk
        $newMechanic = $this->mechanic();
        $this->service()->update($order, [
            'customer_id' => $order->customer_id,
            'vehicle_id' => $order->vehicle_id,
            'mechanic_id' => $newMechanic->getKey(),
            'complaint' => 'Keluhan diperbarui',
        ], $admin);

        $this->assertSame(MechanicStatus::Tersedia, $mechanic->fresh()->status);
        $this->assertSame(MechanicStatus::Sibuk, $newMechanic->fresh()->status);

        // Setelah dikerjakan: tidak bisa diubah / dihapus
        $this->service()->start($order->fresh(), $newMechanic->user);

        $this->assertFalse($admin->can('update', $order->fresh()));
        $this->assertFalse($admin->can('delete', $order->fresh()));

        $this->expectException(AuthorizationException::class);
        $this->service()->delete($order->fresh(), $admin);
    }

    public function test_hapus_wo_baru_mengembalikan_mekanik_tersedia(): void
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);

        $this->service()->delete($order, $this->admin());

        $this->assertModelMissing($order);
        $this->assertSame(MechanicStatus::Tersedia, $mechanic->fresh()->status);
    }
}
