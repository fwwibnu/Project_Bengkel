<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

/**
 * Work Order:
 * - Admin   : kelola (buat, lihat, ubah/hapus selama Baru & belum ada sparepart)
 * - Mekanik : "work" = kerjakan WO miliknya sendiri (lewat halaman Work Order Saya)
 * - Kasir   : "pay"  = bayar WO (status dicek ulang di PaymentService)
 * - Pemilik : tidak bisa mengubah apa pun
 */
class WorkOrderPolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->isAdmin() && $workOrder->isEditable();
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->isAdmin() && $workOrder->isEditable();
    }

    /** Mekanik hanya boleh mengerjakan WO yang ditugaskan kepadanya. */
    public function work(User $user, WorkOrder $workOrder): bool
    {
        if (! $user->isMekanik()) {
            return false;
        }

        $mechanicUserId = $workOrder->mechanic()->value('user_id');

        return $mechanicUserId !== null && (int) $mechanicUserId === (int) $user->getKey();
    }

    public function pay(User $user, WorkOrder $workOrder): bool
    {
        // Status (harus Selesai, belum dibayar) dicek di PaymentService supaya pesannya jelas
        return $user->isKasir();
    }
}
