<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

/** Kendaraan: hanya Admin. Kasir melihat riwayat servis lewat halaman "Riwayat Servis" (read-only). */
class VehiclePolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->isAdmin();
    }
}
