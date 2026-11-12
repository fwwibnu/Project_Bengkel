{{--
    Dashboard Admin & Pemilik. Data dari Dashboard::getViewData().
    Animasi: blok naik berurutan (.bk-rise), angka berjalan (<x-bk-count>),
    batang grafik tumbuh (.bk-bar), bar stok terisi (.bk-fill). Semua di theme.css.
--}}
<x-filament-panels::page>
    <div class="flex flex-col gap-6">
 
        {{-- ================= Pendapatan bulan ini + grafik 6 bulan ================= --}}
        <section class="bk-card bk-rise flex flex-wrap items-end gap-8 p-6 sm:p-8" style="animation-delay: 60ms">
            <div class="min-w-0 flex-[1_1_300px]">
                <p class="bk-muted text-sm font-semibold">Pendapatan {{ $monthName }}</p>
                <p class="mt-2 text-[clamp(2.5rem,5.5vw,4.25rem)] font-extrabold leading-none tracking-[-0.045em] tabular-nums text-gray-950">
                    <x-bk-count :value="$month['total']" prefix="Rp " />
                </p>
 
                <div class="mt-6 flex flex-wrap gap-x-8 gap-y-4">
                    <div>
                        <p class="bk-muted text-[13px]">Transaksi</p>
                        <p class="mt-0.5 text-xl font-bold tracking-tight tabular-nums text-gray-950"><x-bk-count :value="$month['count']" /></p>
                    </div>
                    <div>
                        <p class="bk-muted text-[13px]">Dari jasa</p>
                        <p class="mt-0.5 text-xl font-bold tracking-tight tabular-nums text-gray-950"><x-bk-count :value="$month['fee']" prefix="Rp " /></p>
                    </div>
                    <div>
                        <p class="bk-muted text-[13px]">Dari sparepart</p>
                        <p class="mt-0.5 text-xl font-bold tracking-tight tabular-nums text-gray-950"><x-bk-count :value="$month['parts']" prefix="Rp " /></p>
                    </div>
                </div>
            </div>
 
            <div class="min-w-0 flex-[1_1_340px]">
                <div class="mb-3 flex items-baseline justify-between gap-3">
                    <p class="bk-muted text-[13px] font-semibold">Pendapatan 6 bulan terakhir</p>
                    <p class="bk-muted hidden text-xs sm:block">sorot batang untuk detail</p>
                </div>
 
                {{-- min-w-0 + keterangan "absolute": lebar kolom tidak ikut melebar karena teks keterangan --}}
                <div class="flex h-44 items-end gap-3 pt-7" role="img" aria-label="Grafik pendapatan 6 bulan terakhir">
                    @foreach ($bars as $bar)
                        <div class="bk-bar-col flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2" tabindex="0">
                            <div class="relative flex w-full flex-1 items-end justify-center">
                                <div class="relative w-full max-w-[44px]" style="height: {{ $bar['height'] }}%">
                                    <span class="bk-bar-tip pointer-events-none absolute bottom-full -left-16 -right-16 mb-2 whitespace-nowrap text-center text-[11px] font-bold text-gray-950">{{ $bar['tip'] }}</span>
                                    <div class="bk-bar h-full w-full rounded-[10px] {{ $bar['current'] ? 'bg-primary-600' : 'bk-track' }}"
                                         style="animation-delay: {{ $bar['delay'] }}ms"></div>
                                </div>
                            </div>
                            <span class="bk-muted text-xs {{ $bar['current'] ? 'font-semibold text-gray-950' : '' }}">{{ $bar['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
 
        {{-- ================= 4 kartu ringkas ================= --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="bk-card bk-lift bk-rise p-6" style="animation-delay: 160ms">
                <p class="bk-muted text-[13px] font-semibold">Total stok</p>
                <p class="mt-2.5 text-4xl font-extrabold tracking-[-0.04em] tabular-nums text-gray-950">
                    <x-bk-count :value="$stats['units']" />
                    <span class="bk-muted text-base font-semibold tracking-normal">unit</span>
                </p>
                <p class="bk-muted mt-1.5 text-[13px]">dari {{ $stats['types'] }} jenis sparepart</p>
            </div>
 
            <div class="bk-card bk-lift bk-rise p-6" style="animation-delay: 230ms">
                <p class="bk-muted text-[13px] font-semibold">Stok menipis</p>
                <p class="mt-2.5 text-4xl font-extrabold tracking-[-0.04em] {{ $stats['low'] > 0 ? 'text-primary-600' : 'text-gray-950' }}">
                    {{ $stats['low'] }}
                    <span class="text-base font-semibold tracking-normal">item</span>
                </p>
                <p class="bk-muted mt-1.5 text-[13px]">
                    {{ $stats['out'] > 0 ? $stats['out'].' di antaranya habis' : 'di bawah batas minimal' }}
                </p>
            </div>
 
            <div class="bk-card bk-lift bk-rise p-6" style="animation-delay: 300ms">
                <p class="bk-muted text-[13px] font-semibold">Work Order aktif</p>
                <p class="mt-2.5 text-4xl font-extrabold tracking-[-0.04em] text-gray-950">{{ $counts['baru'] + $counts['dikerjakan'] }}</p>
                <p class="bk-muted mt-1.5 text-[13px]">{{ $counts['dikerjakan'] }} dikerjakan · {{ $counts['baru'] }} baru</p>
            </div>
 
            <div class="bk-card bk-lift bk-rise p-6" style="animation-delay: 370ms">
                <p class="bk-muted text-[13px] font-semibold">Menunggu bayar</p>
                <p class="mt-2.5 text-4xl font-extrabold tracking-[-0.04em] text-gray-950">{{ $counts['selesai'] }}</p>
                <p class="bk-muted mt-1.5 text-[13px]">sudah selesai diservis</p>
            </div>
        </div>
 
        {{-- ================= Mekanik + stok menipis ================= --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
 
            <section class="bk-card bk-lift bk-rise p-6 sm:p-7" style="animation-delay: 450ms">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-xl font-bold tracking-tight text-gray-950">Mekanik hari ini</h2>
                    @if ($mechanicUrl)
                        <a href="{{ $mechanicUrl }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lihat semua</a>
                    @endif
                </div>
 
                <div class="mt-3 flex flex-col">
                    @forelse ($mechanics as $m)
                        @php
                            $isTersedia = $m['status'] === \App\Enums\MechanicStatus::Tersedia;
                            $isLibur = $m['status'] === \App\Enums\MechanicStatus::Libur;
                        @endphp
                        <div class="flex items-center gap-3.5 py-3.5 {{ $loop->last ? '' : 'border-b border-gray-950/[0.06]' }}">
                            <span class="bk-soft flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[13px] font-bold {{ $isLibur ? 'bk-muted' : 'text-gray-950' }}">{{ $m['initials'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-semibold {{ $isLibur ? 'bk-muted' : 'text-gray-950' }}">{{ $m['name'] }}</p>
                                <p class="bk-muted truncate text-[13px]">{{ $m['note'] }}</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-2 text-[13px] font-semibold {{ $isTersedia ? 'text-green-700' : ($isLibur ? 'bk-muted' : 'text-primary-600') }}">
                                <span class="h-2 w-2 rounded-full {{ $isTersedia ? 'bk-pulse bg-green-500' : ($isLibur ? 'bg-gray-400' : 'bg-primary-600') }}"></span>
                                {{ $m['status']->getLabel() }}
                            </span>
                        </div>
                    @empty
                        <p class="bk-muted py-6 text-center text-sm">Belum ada data mekanik.</p>
                    @endforelse
                </div>
            </section>
 
            <section class="bk-card bk-lift bk-rise p-6 sm:p-7" style="animation-delay: 520ms">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-xl font-bold tracking-tight text-gray-950">Perlu dibeli lagi</h2>
                    @if ($sparepartUrl)
                        <a href="{{ $sparepartUrl }}" wire:navigate class="text-sm font-semibold text-primary-600 hover:text-primary-700">Buka sparepart</a>
                    @endif
                </div>
 
                <div class="mt-5 flex flex-col gap-5">
                    @forelse ($lowStock as $i => $part)
                        @php
                            $pct = $part->min_stock > 0 ? min(100, (int) round($part->stock / $part->min_stock * 100)) : 0;
                            $habis = $part->isOutOfStock();
                        @endphp
                        <div>
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="truncate text-[15px] font-semibold text-gray-950">
                                    {{ $part->name }} <span class="bk-muted font-medium">· {{ $part->code }}</span>
                                </p>
                                <p class="shrink-0 text-sm font-bold tabular-nums {{ $habis ? 'text-red-700' : 'text-primary-600' }}">
                                    {{ $habis ? 'Habis' : $part->stock }}
                                    <span class="bk-muted font-medium">/ min {{ $part->min_stock }}</span>
                                </p>
                            </div>
                            <div class="bk-track mt-2.5 h-2 overflow-hidden rounded-full">
                                <div class="bk-fill h-full rounded-full {{ $habis ? 'bg-red-600' : 'bg-primary-600' }}"
                                     style="width: {{ $pct }}%; animation-delay: {{ 650 + $i * 100 }}ms"></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center gap-2 py-6 text-center">
                            <x-filament::icon icon="heroicon-o-check-badge" class="h-8 w-8 text-green-600" />
                            <p class="text-sm font-semibold text-gray-950">Semua stok aman</p>
                            <p class="bk-muted text-sm">Tidak ada sparepart yang menipis atau habis.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
 