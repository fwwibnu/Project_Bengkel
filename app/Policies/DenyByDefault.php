<?php

namespace App\Policies;

use App\Models\User;

/**
 * Aksi massal / soft-delete / urutkan selalu ditolak.
 * Filament menganggap aksi yang tidak punya method di Policy sebagai "boleh",
 * jadi semua method ini ditulis eksplisit supaya tidak ada celah.
 */
trait DenyByDefault
{
    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, mixed $model = null): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, mixed $model = null): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, mixed $model = null): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
