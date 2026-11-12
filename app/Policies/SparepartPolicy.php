<?php

namespace App\Policies;

use App\Models\Sparepart;
use App\Models\User;

/** Sparepart: hanya Admin (Pemilik melihat lewat dashboard & laporan). */
class SparepartPolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Sparepart $sparepart): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Sparepart $sparepart): bool
    {
        return $user->isAdmin();
    }

    public function addStock(User $user, Sparepart $sparepart): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Sparepart $sparepart): bool
    {
        return $user->isAdmin();
    }
}
