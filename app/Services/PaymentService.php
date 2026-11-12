<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Gate;

/**
 * Pembayaran oleh Kasir. Input dari browser HANYA biaya jasa dan metode;
 * total sparepart dan total bayar dihitung ulang dari database di dalam transaksi.
 * Satu WO hanya boleh satu pembayaran (dicek di sini + UNIQUE di tabel payments).
 */
class PaymentService
{
    public const MAX_SERVICE_FEE = 100_000_000;

    public function __construct(
        protected MechanicService $mechanics,
    ) {}

    public function pay(WorkOrder $workOrder, int $serviceFee, PaymentMethod|string $method, User $by): Payment
    {
        Gate::forUser($by)->authorize('pay', $workOrder);

        if ($serviceFee < 0) {
            throw new BusinessRuleException('Biaya jasa tidak boleh negatif.');
        }

        if ($serviceFee > self::MAX_SERVICE_FEE) {
            throw new BusinessRuleException('Biaya jasa terlalu besar. Periksa lagi angkanya.');
        }

        $method = $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom($method);

        if (! $method) {
            throw new BusinessRuleException('Metode pembayaran tidak dikenal.');
        }

        return NumberGenerator::retry(function () use ($workOrder, $serviceFee, $method, $by) {
            $workOrder = WorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();

            if ($workOrder->status === WorkOrderStatus::Dibayar || Payment::query()->where('work_order_id', $workOrder->getKey())->exists()) {
                throw new BusinessRuleException('Work Order ini sudah dibayar.');
            }

            if ($workOrder->status !== WorkOrderStatus::Selesai) {
                throw new BusinessRuleException('Hanya Work Order berstatus Selesai yang bisa dibayar.');
            }

            $sparepartTotal = (int) $workOrder->items()->sum('subtotal');
            $paidAt = now();

            $payment = Payment::create([
                'number' => NumberGenerator::payment($paidAt),
                'work_order_id' => $workOrder->getKey(),
                'cashier_id' => $by->getKey(),
                'sparepart_total' => $sparepartTotal,
                'service_fee' => $serviceFee,
                'grand_total' => $sparepartTotal + $serviceFee,
                'method' => $method,
                'paid_at' => $paidAt,
            ]);

            $workOrder->status = WorkOrderStatus::Dibayar;
            $workOrder->paid_at = $paidAt;
            $workOrder->save();

            $this->mechanics->refreshStatus($workOrder->mechanic_id);

            return $payment;
        });
    }
}
