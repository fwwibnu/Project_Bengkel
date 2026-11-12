<?php

namespace App\Http\Responses;

use App\Support\RoleHome;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Setelah login, arahkan ke halaman awal sesuai peran:
 * Admin & Pemilik → Dashboard, Mekanik → Work Order Saya, Kasir → Pembayaran.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->to(RoleHome::url($request->user()));
    }
}
