<?php

namespace Tests\Concerns;

use App\Models\Mechanic;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;

/** Pembuat data uji yang dipakai bersama oleh feature test. */
trait CreatesBengkelData
{
    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function kasir(): User
    {
        return User::factory()->kasir()->create();
    }

    protected function pemilik(): User
    {
        return User::factory()->pemilik()->create();
    }

    protected function mechanic(array $attributes = []): Mechanic
    {
        return Mechanic::factory()->create($attributes);
    }

    protected function sparepart(int $stock = 10, int $price = 55_000): Sparepart
    {
        return Sparepart::factory()->create(['stock' => $stock, 'selling_price' => $price]);
    }

    /** WO berstatus Baru lewat WorkOrderService. */
    protected function newWorkOrder(?Mechanic $mechanic = null, ?Vehicle $vehicle = null): WorkOrder
    {
        $mechanic ??= $this->mechanic();
        $vehicle ??= Vehicle::factory()->create();

        return app(WorkOrderService::class)->create([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->getKey(),
            'mechanic_id' => $mechanic->getKey(),
            'complaint' => 'Rem depan bunyi',
        ], $this->admin());
    }

    /** WO berstatus Sedang Dikerjakan. @return array{0: WorkOrder, 1: Mechanic} */
    protected function workingOrder(): array
    {
        $mechanic = $this->mechanic();
        $order = $this->newWorkOrder($mechanic);

        app(WorkOrderService::class)->start($order, $mechanic->user);

        return [$order->fresh(), $mechanic->fresh()];
    }

    /** WO berstatus Selesai. @return array{0: WorkOrder, 1: Mechanic} */
    protected function finishedOrder(?Sparepart $sparepart = null, int $qty = 0): array
    {
        [$order, $mechanic] = $this->workingOrder();

        if ($sparepart && $qty > 0) {
            app(\App\Services\StockService::class)->recordUsage($order, $sparepart->getKey(), $qty, $mechanic->user);
        }

        app(WorkOrderService::class)->finish($order, $mechanic->user, 'Ganti kampas rem');

        return [$order->fresh(), $mechanic->fresh()];
    }
}
