{{-- Kasir: ringkasan di atas + daftar Work Order siap bayar sebagai kartu (.bk-bayar di theme.css) --}}
<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <div class="grid grid-cols-2 gap-4 sm:max-w-md">
            <div class="bk-card bk-lift bk-rise px-5 py-4" style="animation-delay: 60ms">
                <p class="bk-muted text-xs font-semibold">Menunggu dibayar</p>
                <p class="mt-1 text-[1.75rem] font-extrabold leading-tight tracking-[-0.03em] text-gray-950">{{ $waiting }}</p>
            </div>
            <div class="bk-card bk-lift bk-rise px-5 py-4" style="animation-delay: 130ms">
                <p class="bk-muted text-xs font-semibold">Masuk hari ini · {{ $todayCount }} transaksi</p>
                <p class="mt-1 text-[1.75rem] font-extrabold leading-tight tracking-[-0.03em] tabular-nums text-gray-950">
                    <x-bk-count :value="$todayTotal" prefix="Rp " />
                </p>
            </div>
        </div>

        <div class="bk-bayar">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
