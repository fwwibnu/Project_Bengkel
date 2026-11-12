<?php

namespace Tests\Feature;

use App\Enums\MechanicStatus;
use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** F8: pembayaran oleh Kasir, total dihitung server, satu WO satu pembayaran. */
class PaymentTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    protected function payments(): PaymentService
    {
        return app(PaymentService::class);
    }

    public function test_pembayaran_menghitung_total_dan_mengubah_status(): void
    {
        $sparepart = $this->sparepart(stock: 10, price: 25_000);
        [$order, $mechanic] = $this->finishedOrder($sparepart, 2); // 50.000

        $payment = $this->payments()->pay($order, 75_000, PaymentMethod::Tunai, $this->kasir());

        $this->assertSame(50_000, $payment->sparepart_total);
        $this->assertSame(75_000, $payment->service_fee);
        $this->assertSame(125_000, $payment->grand_total);
        $this->assertMatchesRegularExpression('/^TRX-\d{8}-001$/', $payment->number);

        $order->refresh();
        $this->assertSame(WorkOrderStatus::Dibayar, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(MechanicStatus::Tersedia, $mechanic->fresh()->status);
    }

    public function test_tidak_bisa_bayar_dua_kali(): void
    {
        [$order] = $this->finishedOrder();
        $kasir = $this->kasir();

        $this->payments()->pay($order, 50_000, 'tunai', $kasir);

        try {
            $this->payments()->pay($order->fresh(), 50_000, 'tunai', $kasir);
            $this->fail('Bayar dua kali seharusnya ditolak');
        } catch (BusinessRuleException $e) {
            $this->assertSame('Work Order ini sudah dibayar.', $e->getMessage());
        }

        $this->assertSame(1, Payment::count());
    }

    public function test_wo_yang_belum_selesai_tidak_bisa_dibayar(): void
    {
        [$order] = $this->workingOrder();

        $this->expectException(BusinessRuleException::class);
        $this->payments()->pay($order, 50_000, 'tunai', $this->kasir());
    }

    public function test_biaya_jasa_negatif_ditolak(): void
    {
        [$order] = $this->finishedOrder();

        $this->expectException(BusinessRuleException::class);
        $this->payments()->pay($order, -1, 'tunai', $this->kasir());
    }

    public function test_hanya_kasir_yang_bisa_memproses_pembayaran(): void
    {
        [$order, $mechanic] = $this->finishedOrder();

        foreach ([$this->admin(), $this->pemilik(), $mechanic->user] as $user) {
            try {
                $this->payments()->pay($order, 50_000, 'tunai', $user);
                $this->fail('Seharusnya ditolak untuk '.$user->role->value);
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, Payment::count());
    }
}
