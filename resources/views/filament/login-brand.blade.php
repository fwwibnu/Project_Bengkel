{{-- Panel brand di sisi kiri halaman login (hanya layar lebar). Diposisikan fixed lewat theme.css (.bk-login-brand). --}}
<div class="bk-login-brand" aria-hidden="true">
    <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-500">
            <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-6 w-6 text-white" />
        </span>
        <span class="text-lg font-bold text-white">{{ config('bengkel.nama') }}</span>
    </div>

    <div class="max-w-md space-y-4">
        <p class="text-4xl font-bold leading-tight text-white">Sistem Informasi Pengelolaan Bengkel</p>
        <p class="text-base leading-relaxed text-slate-400">
            Kelola pelanggan, Work Order, stok sparepart, dan pembayaran dalam satu tempat.
        </p>
    </div>

    <p class="text-sm text-slate-400">Khusus staf bengkel. Akun dibuat oleh Admin.</p>
</div>
