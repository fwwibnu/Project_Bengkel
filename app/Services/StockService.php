<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\WorkOrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Sparepart;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Semua perubahan stok lewat sini, masing-masing dalam satu DB::transaction
 * dengan baris sparepart dikunci (lockForUpdate). Setiap perubahan dicatat
 * di stock_movements supaya laporan sirkulasi stok lengkap.
 */
class StockService
{
    /**
     * Admin membuat sparepart baru. Kode kosong → dibuat otomatis (SP-NNN).
     * Stok awal (kalau diisi) dicatat sebagai stok masuk "Stok awal".
     *
     * @param  array{code?:?string, name:string, stock?:int|string|null, min_stock:int|string, purchase_price:int|string, selling_price:int|string}  $data
     */
    public function createSparepart(array $data, User $by): Sparepart
    {
        Gate::forUser($by)->authorize('create', Sparepart::class);

        $initial = (int) ($data['stock'] ?? 0);

        if ($initial < 0) {
            throw new BusinessRuleException('Stok awal tidak boleh negatif.');
        }

        return NumberGenerator::retry(function () use ($data, $by, $initial) {
            $sparepart = new Sparepart([
                'name' => trim((string) $data['name']),
                'min_stock' => (int) $data['min_stock'],
                'purchase_price' => (int) $data['purchase_price'],
                'selling_price' => (int) $data['selling_price'],
            ]);
            $sparepart->code = filled($data['code'] ?? null)
                ? strtoupper(trim((string) $data['code']))
                : NumberGenerator::sparepartCode();
            $sparepart->stock = 0;
            $sparepart->save();

            if ($initial > 0) {
                $this->applyIn($sparepart, $initial, StockMovementType::Masuk, 'Stok awal', $by);
            }

            return $sparepart;
        });
    }

    /** Admin: Tambah Stok (stok masuk) dengan keterangan. */
    public function addStock(Sparepart $sparepart, int $qty, ?string $note, User $by): StockMovement
    {
        Gate::forUser($by)->authorize('addStock', $sparepart);

        if ($qty < 1) {
            throw new BusinessRuleException('Jumlah stok masuk minimal 1.');
        }

        return DB::transaction(function () use ($sparepart, $qty, $note, $by) {
            $sparepart = $this->lockSparepart($sparepart->getKey());

            return $this->applyIn($sparepart, $qty, StockMovementType::Masuk, filled($note) ? trim($note) : null, $by);
        });
    }

    /**
     * Mekanik mencatat sparepart terpakai di WO miliknya (status Sedang Dikerjakan).
     * Harga jual saat ini dikunci di item. Stok langsung berkurang.
     * Sparepart yang sama dicatat lagi → digabung ke baris yang sudah ada.
     */
    public function recordUsage(WorkOrder $workOrder, int $sparepartId, int $qty, User $by): WorkOrderItem
    {
        Gate::forUser($by)->authorize('work', $workOrder);

        if ($qty < 1) {
            throw new BusinessRuleException('Jumlah sparepart minimal 1.');
        }

        return DB::transaction(function () use ($workOrder, $sparepartId, $qty, $by) {
            $workOrder = WorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();

            if ($workOrder->status !== WorkOrderStatus::Dikerjakan) {
                throw new BusinessRuleException('Sparepart hanya bisa dicatat saat Work Order berstatus Sedang Dikerjakan.');
            }

            $sparepart = $this->lockSparepart($sparepartId);

            if ($sparepart->stock < $qty) {
                throw new BusinessRuleException(sprintf(
                    'Stok tidak cukup. Sisa stok %s: %d.',
                    $sparepart->name,
                    $sparepart->stock,
                ));
            }

            $sparepart->stock -= $qty;
            $sparepart->save();

            $item = WorkOrderItem::query()
                ->where('work_order_id', $workOrder->getKey())
                ->where('sparepart_id', $sparepart->getKey())
                ->lockForUpdate()
                ->first();

            if ($item) {
                // Harga tetap harga yang dikunci saat pertama dicatat
                $item->qty += $qty;
                $item->subtotal = $item->qty * $item->unit_price;
                $item->save();
            } else {
                $item = WorkOrderItem::create([
                    'work_order_id' => $workOrder->getKey(),
                    'sparepart_id' => $sparepart->getKey(),
                    'qty' => $qty,
                    'unit_price' => $sparepart->selling_price,
                    'subtotal' => $qty * $sparepart->selling_price,
                ]);
            }

            StockMovement::create([
                'sparepart_id' => $sparepart->getKey(),
                'type' => StockMovementType::Keluar,
                'qty' => $qty,
                'work_order_id' => $workOrder->getKey(),
                'reference' => $workOrder->number,
                'user_id' => $by->getKey(),
            ]);

            return $item;
        });
    }

    /** Mekanik menghapus sparepart salah catat (hanya saat Sedang Dikerjakan). Stok dikembalikan. */
    public function removeItem(WorkOrderItem $item, User $by): void
    {
        $workOrder = $item->workOrder()->firstOrFail();

        Gate::forUser($by)->authorize('work', $workOrder);

        DB::transaction(function () use ($item, $workOrder, $by) {
            $workOrder = WorkOrder::query()->whereKey($workOrder->getKey())->lockForUpdate()->firstOrFail();

            if ($workOrder->status !== WorkOrderStatus::Dikerjakan) {
                throw new BusinessRuleException('Sparepart hanya bisa dihapus selama Work Order masih Sedang Dikerjakan.');
            }

            $item = WorkOrderItem::query()
                ->whereKey($item->getKey())
                ->where('work_order_id', $workOrder->getKey())
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw new BusinessRuleException('Sparepart ini sudah dihapus sebelumnya.');
            }

            $sparepart = $this->lockSparepart($item->sparepart_id);

            $this->applyIn($sparepart, $item->qty, StockMovementType::Batal, 'Salah catat, stok dikembalikan', $by, $workOrder);

            $item->delete();
        });
    }

    protected function lockSparepart(int $id): Sparepart
    {
        $sparepart = Sparepart::query()->whereKey($id)->lockForUpdate()->first();

        if (! $sparepart) {
            throw new BusinessRuleException('Sparepart tidak ditemukan.');
        }

        return $sparepart;
    }

    /** Tambah stok + catat pergerakan (masuk / batal). Dipanggil di dalam transaksi. */
    protected function applyIn(
        Sparepart $sparepart,
        int $qty,
        StockMovementType $type,
        ?string $note,
        User $by,
        ?WorkOrder $workOrder = null,
    ): StockMovement {
        $sparepart->stock += $qty;
        $sparepart->save();

        return StockMovement::create([
            'sparepart_id' => $sparepart->getKey(),
            'type' => $type,
            'qty' => $qty,
            'work_order_id' => $workOrder?->getKey(),
            'reference' => $workOrder?->number,
            'note' => $note,
            'user_id' => $by->getKey(),
        ]);
    }
}
