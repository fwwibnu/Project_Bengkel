<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Pembayaran: dibuat hanya lewat PaymentService (Kasir).
 * Riwayat transaksi: Kasir & Admin. Nota: Kasir, Admin, Pemilik (lihat saja).
 */
class PaymentPolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isKasir() || $user->isAdmin();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isKasir() || $user->isAdmin() || $user->isPemilik();
    }

    public function create(User $user): bool
    {
        return false; // lewat halaman Pembayaran → PaymentService
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
