{{--
    Halaman Mekanik (HP-first). Semua data dari WorkOrderSaya.php yang sudah
    dibatasi ke Work Order milik mekanik yang login.
--}}
@php
    $mechanic = $this->getMechanic();
    $selected = $this->getSelected();
    $fmt = \App\Support\Format::class;
    $Status = \App\Enums\WorkOrderStatus::class;
@endphp

<x-filament-panels::page>
    <div class="mx-auto w-full max-w-2xl space-y-4">

        @if (! $mechanic)
            {{-- Akun mekanik belum terhubung ke data mekanik --}}
            <div class="bk-card flex flex-col items-center gap-3 p-6 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50">
                    <x-filament::icon icon="heroicon-o-user-circle" class="h-6 w-6 text-primary-600" />
                </span>
                <p class="text-base font-bold text-gray-950">Akun kamu belum terhubung ke data mekanik</p>
                <p class="text-sm text-gray-500">Minta Admin menghubungkan akunmu di menu Mekanik supaya Work Order bisa ditugaskan.</p>
            </div>

        @elseif ($this->wo && ! $selected)
            <div class="bk-card flex flex-col items-center gap-3 p-6 text-center">
                <p class="text-base font-bold text-gray-950">Work Order tidak ditemukan</p>
                <p class="text-sm text-gray-500">Work Order ini tidak ada atau bukan milikmu.</p>
                <x-filament::button color="gray" wire:click="back">Kembali ke daftar</x-filament::button>
            </div>

        @elseif ($selected)
            {{-- ===================== DETAIL ===================== --}}
            @php $dikerjakan = $selected->status === $Status::Dikerjakan; @endphp
            <button type="button" wire:click="back"
                    class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700">
                <x-filament::icon icon="heroicon-m-arrow-left" class="h-4 w-4" />
                Kembali ke daftar
            </button>

            <section class="bk-card space-y-4 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">{{ $selected->number }} · {{ $fmt::tanggalJam($selected->created_at) }}</p>
                        <p class="text-2xl font-bold tracking-tight text-gray-950">{{ $selected->vehicle?->plate_number }}</p>
                        <p class="text-sm text-gray-600">
                            {{ trim(($selected->vehicle?->brand ?? '') . ' ' . ($selected->vehicle?->model ?? '')) }} · {{ $selected->customer?->name }}
                        </p>
                    </div>
                    <x-filament::badge :color="$selected->status->getColor()">{{ $selected->status->getLabel() }}</x-filament::badge>
                </div>

                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-xs font-semibold text-gray-500">Keluhan</p>
                    <p class="text-sm text-gray-950">{{ $selected->complaint }}</p>
                </div>

                @if ($selected->status !== $Status::Baru)
                    <div class="rounded-lg bg-gray-50 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-gray-500">Pekerjaan yang dilakukan</p>
                            @if ($dikerjakan)
                                {{ ($this->catatanAction)(['wo' => $selected->id]) }}
                            @endif
                        </div>
                        <p class="text-sm text-gray-950">{{ $selected->work_notes ?: 'Belum diisi.' }}</p>
                    </div>
                @endif
            </section>

            {{-- Sparepart terpakai --}}
            <section class="bk-card space-y-3 p-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-950">Sparepart terpakai</h2>
                    <span class="text-xs text-gray-500">{{ $selected->items->count() }} item</span>
                </div>

                @forelse ($selected->items as $item)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950">{{ $item->sparepart?->name }}</p>
                            <p class="text-xs text-gray-500">{{ $item->sparepart?->code }} · {{ $item->qty }} pcs</p>
                        </div>
                        @if ($dikerjakan)
                            {{ ($this->hapusItemAction)(['item' => $item->id]) }}
                        @endif
                    </div>
                @empty
                    <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-sm text-gray-500">
                        @if ($selected->status === $Status::Baru)
                            Mulai kerjakan dulu, baru sparepart bisa dicatat.
                        @else
                            Belum ada sparepart dicatat.
                        @endif
                    </p>
                @endforelse

                @if ($dikerjakan)
                    {{ ($this->catatSparepartAction)(['wo' => $selected->id]) }}
                    <p class="text-xs leading-relaxed text-gray-500">
                        Stok langsung berkurang saat sparepart dicatat. Hapus hanya bisa selama status masih Sedang Dikerjakan.
                    </p>
                @endif
            </section>

            {{-- Aksi utama, lebar penuh supaya mudah ditekan jempol --}}
            <div class="space-y-2">
                @if ($selected->status === $Status::Baru)
                    {{ ($this->mulaiAction)(['wo' => $selected->id]) }}
                @elseif ($dikerjakan)
                    {{ ($this->selesaiAction)(['wo' => $selected->id]) }}
                @else
                    <p class="text-center text-sm text-gray-500">
                        Work Order ini sudah {{ strtolower($selected->status->getLabel()) }}. Tidak ada aksi lagi.
                    </p>
                @endif
            </div>

        @else
            {{-- ===================== DAFTAR ===================== --}}
            @php
                $active = $this->getActiveOrders();
                $history = $this->getHistory();
            @endphp

            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-gray-600">Halo, <span class="font-semibold text-gray-950">{{ $mechanic->name }}</span></p>
                <span class="flex items-center gap-2 text-sm text-gray-600">
                    Status kamu
                    <x-filament::badge :color="$mechanic->status->getColor()">{{ $mechanic->status->getLabel() }}</x-filament::badge>
                </span>
            </div>

            <h2 class="text-base font-bold text-gray-950">Sedang dipegang</h2>

            @forelse ($active as $order)
                <button type="button" wire:click="open({{ $order->id }})"
                        class="bk-card block w-full space-y-2 p-4 text-left transition hover:ring-primary-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-gray-500">{{ $order->number }} · {{ $fmt::tanggal($order->created_at) }}</span>
                        <x-filament::badge :color="$order->status->getColor()">{{ $order->status->getLabel() }}</x-filament::badge>
                    </div>
                    <p class="text-lg font-bold text-gray-950">
                        {{ $order->vehicle?->plate_number }}
                        <span class="text-sm font-medium text-gray-500">· {{ trim(($order->vehicle?->brand ?? '') . ' ' . ($order->vehicle?->model ?? '')) }}</span>
                    </p>
                    <p class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($order->complaint, 90) }}</p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $order->customer?->name }} · {{ $order->items_count }} sparepart</span>
                        <span class="inline-flex items-center gap-1 font-semibold text-primary-600">
                            Buka <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4" />
                        </span>
                    </div>
                </button>
            @empty
                <div class="bk-card flex flex-col items-center gap-2 p-6 text-center">
                    <x-filament::icon icon="heroicon-o-check-badge" class="h-8 w-8 text-green-600" />
                    <p class="text-sm font-semibold text-gray-950">Tidak ada Work Order aktif</p>
                    <p class="text-sm text-gray-500">Work Order baru dari Admin akan muncul di sini.</p>
                </div>
            @endforelse

            <h2 class="pt-2 text-base font-bold text-gray-950">Riwayat terakhir</h2>

            @forelse ($history as $order)
                <div class="bk-card flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-950">
                            {{ $order->vehicle?->plate_number }} · {{ trim(($order->vehicle?->brand ?? '') . ' ' . ($order->vehicle?->model ?? '')) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $order->number }} · selesai {{ $fmt::tanggal($order->finished_at) }}</p>
                    </div>
                    <x-filament::badge :color="$order->status->getColor()">{{ $order->status->getLabel() }}</x-filament::badge>
                </div>
            @empty
                <p class="text-sm text-gray-500">Belum ada Work Order yang selesai.</p>
            @endforelse
        @endif
    </div>
</x-filament-panels::page>
