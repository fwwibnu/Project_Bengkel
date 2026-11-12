<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/** Pelanggan: hanya Admin. */
class CustomerPolicy
{
    use DenyByDefault;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
