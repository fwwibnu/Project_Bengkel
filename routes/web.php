<?php

use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotaController;
use App\Support\RoleHome;
use Illuminate\Support\Facades\Route;

// Tidak ada halaman publik. "/" langsung ke halaman awal sesuai peran (atau ke login).
Route::get('/', fn () => redirect()->to(RoleHome::url(auth()->user())))->name('home');

// Halaman siap cetak (Blade + Tailwind). Otorisasi per peran dicek di controller.
Route::middleware('auth')->group(function () {
    Route::get('/nota/{payment}', [NotaController::class, 'show'])->name('nota.show');
    Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
});
