<?php

namespace App\Policies;

use App\Models\Mechanic;
use App\Models\User;

/** Data mekanik: hanya Admin. */
class MechanicPolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Mechanic $mechanic): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Mechanic $mechanic): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Mechanic $mechanic): bool
    {
        return $user->isAdmin();
    }
}
