{{-- Halaman Laporan (Admin & Pemilik, hanya baca) --}}
<x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-filament::tabs label="Jenis laporan">
                <x-filament::tabs.item :active="$jenis === 'pendapatan'" wire:click="setTab('pendapatan')" icon="heroicon-m-banknotes">
                    Pendapatan
                </x-filament::tabs.item>
                <x-filament::tabs.item :active="$jenis === 'servis'" wire:click="setTab('servis')" icon="heroicon-m-clipboard-document-list">
                    Transaksi Servis
                </x-filament::tabs.item>
                <x-filament::tabs.item :active="$jenis === 'stok'" wire:click="setTab('stok')" icon="heroicon-m-arrows-right-left">
                    Sirkulasi Stok
                </x-filament::tabs.item>
            </x-filament::tabs>

            <x-filament::button tag="a" :href="$this->getCetakUrl()" target="_blank" icon="heroicon-m-printer">
                Cetak / Simpan PDF
            </x-filament::button>
        </div>

        <div class="bk-card flex flex-wrap items-end gap-4 p-4">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-semibold text-gray-950">Dari tanggal</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="dari" />
                </x-filament::input.wrapper>
            </label>

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-semibold text-gray-950">Sampai tanggal</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="sampai" />
                </x-filament::input.wrapper>
            </label>

            @if ($jenis === 'pendapatan')
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold text-gray-950">Periode</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="periode">
                            <option value="daily">Harian</option>
                            <option value="monthly">Bulanan</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
            @endif

            <div class="flex flex-wrap gap-2">
                <x-filament::button size="sm" color="gray" wire:click="preset('hari-ini')">Hari ini</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('bulan-ini')">Bulan ini</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('bulan-lalu')">Bulan lalu</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="preset('6-bulan')">6 bulan</x-filament::button>
            </div>
        </div>

        <p class="text-sm text-gray-500">Periode: <span class="font-semibold text-gray-950">{{ $rangeLabel }}</span></p>

        <div wire:loading.class="opacity-60">
            @if ($jenis === 'pendapatan')
                @include('laporan._pendapatan', ['data' => $pendapatan, 'periode' => $periode])
            @elseif ($jenis === 'servis')
                @include('laporan._servis', ['data' => $servis])
            @else
                @include('laporan._stok', ['data' => $stok, 'withMovements' => true])
            @endif
        </div>
    </div>
</x-filament-panels::page>
