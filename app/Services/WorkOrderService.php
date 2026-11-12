<?php

namespace App\Services;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Semua perubahan Work Order (buat, ubah, hapus, mulai, selesai) lewat sini.
 *
 *  Baru ──(mekanik: Mulai)──> Sedang Dikerjakan ──(mekanik: Selesai)──> Selesai ──(kasir: Bayar)──> Dibayar
 *
 * Transisi lain ditolak. Pembayaran ada di PaymentService.
 */
class WorkOrderService
{
    public function __construct(
        protected MechanicService $mechanics,
    ) {}

    /**
     * Admin membuat WO. Mekanik wajib Tersedia → setelah disimpan jadi Sibuk.
     *
     * @param  array{customer_id:int, vehicle_id:int, mechanic_id:int, complaint:string}  $data
     */
    public function create(array $data, User $by): WorkOrder
    {
        Gate::forUser($by)->authorize('create', WorkOrder::class);

        $this->ensureVehicleBelongsToCustomer((int) $data['vehicle_id'], (int) $data['customer_id']);

        return NumberGenerator::retry(function () use ($data, $by) {
            $mechanic = $this->lockAvailableMechanic((int) $data['mechanic_id']);

            $workOrder = new WorkOrder([
                'customer_id' => (int) $data['customer_id'],
                'vehicle_id' => (int) $data['vehicle_id'],
                'mechanic_id' => $mechanic->getKey(),
                'complaint' => trim((string) $data['complaint']),
            ]);
            $workOrder->number = NumberGenerator::workOrder();
            $workOrder->created_by = $by->getKey();
            $workOrder->status = WorkOrderStatus::Baru;
            $workOrder->save();

            $mechanic->status = MechanicStatus::Sibuk;
            $mechanic->save();

            return $workOrder;
        });
    }

    /**
     * Admin mengubah WO. Hanya saat status Baru dan belum ada sparepart.
     *
     * @param  array{customer_id:int, vehicle_id:int, mechanic_id:int, complaint:string}  $data
     */
    public function update(WorkOrder $workOrder, array $data, User $by): WorkOrder
    {
        Gate::forUser($by)->authorize('update', $workOrder);

        $this->ensureVehicleBelongsToCustomer((int) $data['vehicle_id'], (int) $data['customer_id']);

        return DB::transaction(function () use ($workOrder, $data) {
            $workOrder = $this->lock($workOrder);
            $this->ensureEditable($workOrder);

            $oldMechanicId = $workOrder->mechanic_id;
            $newMechanicId = (int) $data['mechanic_id'];

            if ($newMechanicId !== (int) $oldMechanicId) {
                $mechanic = $this->lockAvailableMechanic($newMechanicId);
                $mechanic->status = MechanicStatus::Sibuk;
                $mechanic->save();
            }

            $workOrder->fill([
                'customer_id' => (int) $data['customer_id'],
                'vehicle_id' => (int) $data['vehicle_id'],
                'mechanic_id' => $newMechanicId,
                'complaint' => trim((string) $data['complaint']),
            ]);
            $workOrder->save();

            if ($newMechanicId !== (int) $oldMechanicId) {
                $this->mechanics->refreshStatus($oldMechanicId);
            }

            return $workOrder;
        });
    }

    /** Admin menghapus WO Baru tanpa sparepart. Mekanik kembali Tersedia. */
    public function delete(WorkOrder $workOrder, User $by): void
    {
        Gate::forUser($by)->authorize('delete', $workOrder);

        DB::transaction(function () use ($workOrder) {
            $workOrder = $this->lock($workOrder);
            $this->ensureEditable($workOrder);

            $mechanicId = $workOrder->mechanic_id;
            $workOrder->delete();

            $this->mechanics->refreshStatus($mechanicId);
        });
    }

    /** Mekanik pemilik WO: Baru → Sedang Dikerjakan. */
    public function start(WorkOrder $workOrder, User $by): WorkOrder
    {
        Gate::forUser($by)->authorize('work', $workOrder);

        return DB::transaction(function () use ($workOrder) {
            $workOrder = $this->lock($workOrder);
            $this->ensureTransition($workOrder, WorkOrderStatus::Dikerjakan);

            $workOrder->status = WorkOrderStatus::Dikerjakan;
            $workOrder->started_at = now();
            $workOrder->save();

            return $workOrder;
        });
    }

    /** Mekanik pemilik WO: Sedang Dikerjakan → Selesai. Mekanik kembali Tersedia. */
    public function finish(WorkOrder $workOrder, User $by, ?string $workNotes = null): WorkOrder
    {
        Gate::forUser($by)->authorize('work', $workOrder);

        return DB::transaction(function () use ($workOrder, $workNotes) {
            $workOrder = $this->lock($workOrder);
            $this->ensureTransition($workOrder, WorkOrderStatus::Selesai);

            if (filled($workNotes)) {
                $workOrder->work_notes = trim($workNotes);
            }

            $workOrder->status = WorkOrderStatus::Selesai;
            $workOrder->finished_at = now();
            $workOrder->save();

            $this->mechanics->refreshStatus($workOrder->mechanic_id);

            return $workOrder;
        });
    }

    /** Mekanik memperbarui catatan pekerjaan selama Sedang Dikerjakan. */
    public function updateNotes(WorkOrder $workOrder, User $by, ?string $workNotes): WorkOrder
    {
        Gate::forUser($by)->authorize('work', $workOrder);

        return DB::transaction(function () use ($workOrder, $workNotes) {
            $workOrder = $this->lock($workOrder);

            if ($workOrder->status !== WorkOrderStatus::Dikerjakan) {
                throw new BusinessRuleException('Catatan pekerjaan hanya bisa diubah saat Work Order Sedang Dikerjakan.');
            }

            $workOrder->work_notes = filled($workNotes) ? trim($workNotes) : null;
            $workOrder->save();

            return $workOrder;
        });
    }

    protected function lock(WorkOrder $workOrder): WorkOrder
    {
        return WorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();
    }

    protected function lockAvailableMechanic(int $mechanicId): Mechanic
    {
        $mechanic = Mechanic::query()->whereKey($mechanicId)->lockForUpdate()->first();

        if (! $mechanic || $mechanic->status !== MechanicStatus::Tersedia || $mechanic->activeWorkOrders()->exists()) {
            throw new BusinessRuleException('Mekanik yang dipilih sudah tidak tersedia. Pilih mekanik lain.');
        }

        return $mechanic;
    }

    protected function ensureEditable(WorkOrder $workOrder): void
    {
        if (! $workOrder->isEditable()) {
            throw new BusinessRuleException('Work Order ini sudah tidak bisa diubah atau dihapus karena sudah diproses mekanik.');
        }
    }

    protected function ensureTransition(WorkOrder $workOrder, WorkOrderStatus $to): void
    {
        if ($workOrder->status->berikutnya() !== $to) {
            throw new BusinessRuleException(sprintf(
                'Status Work Order tidak bisa diubah dari "%s" ke "%s".',
                $workOrder->status->getLabel(),
                $to->getLabel(),
            ));
        }
    }

    protected function ensureVehicleBelongsToCustomer(int $vehicleId, int $customerId): void
    {
        $ok = Vehicle::query()->whereKey($vehicleId)->where('customer_id', $customerId)->exists();

        if (! $ok) {
            throw new BusinessRuleException('Kendaraan yang dipilih bukan milik pelanggan ini.');
        }
    }
}
