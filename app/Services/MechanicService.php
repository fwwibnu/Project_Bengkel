<?php

namespace App\Services;

use App\Enums\MechanicStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Mechanic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Status mekanik:
 * - Sibuk    : otomatis saat punya Work Order aktif (Baru / Sedang Dikerjakan)
 * - Tersedia : otomatis saat tidak punya Work Order aktif
 * - Libur    : diatur Admin, hanya kalau tidak punya Work Order aktif
 */
class MechanicService
{
    /**
     * Hitung ulang Sibuk/Tersedia dari Work Order aktif. Status Libur tidak disentuh.
     * Dipanggil di dalam transaksi oleh WorkOrderService / PaymentService.
     */
    public function refreshStatus(int|Mechanic|null $mechanic): void
    {
        $id = $mechanic instanceof Mechanic ? $mechanic->getKey() : $mechanic;

        if (! $id) {
            return;
        }

        $mechanic = Mechanic::query()->whereKey($id)->lockForUpdate()->first();

        if (! $mechanic || $mechanic->status === MechanicStatus::Libur) {
            return;
        }

        $mechanic->status = $mechanic->activeWorkOrders()->exists()
            ? MechanicStatus::Sibuk
            : MechanicStatus::Tersedia;

        $mechanic->save();
    }

    public function setLibur(Mechanic $mechanic, User $by): Mechanic
    {
        Gate::forUser($by)->authorize('update', $mechanic);

        return DB::transaction(function () use ($mechanic) {
            $mechanic = Mechanic::query()->whereKey($mechanic->getKey())->lockForUpdate()->firstOrFail();

            if ($mechanic->activeWorkOrders()->exists()) {
                throw new BusinessRuleException('Mekanik ini sedang memegang Work Order aktif, jadi belum bisa diliburkan.');
            }

            $mechanic->status = MechanicStatus::Libur;
            $mechanic->save();

            return $mechanic;
        });
    }

    public function setAktif(Mechanic $mechanic, User $by): Mechanic
    {
        Gate::forUser($by)->authorize('update', $mechanic);

        return DB::transaction(function () use ($mechanic) {
            $mechanic = Mechanic::query()->whereKey($mechanic->getKey())->lockForUpdate()->firstOrFail();

            $mechanic->status = $mechanic->activeWorkOrders()->exists()
                ? MechanicStatus::Sibuk
                : MechanicStatus::Tersedia;
            $mechanic->save();

            return $mechanic;
        });
    }
}
