{{-- Rincian di modal bayar (Kasir). Harga = harga terkunci saat mekanik mencatat. --}}
@php($items = $workOrder->items)

<div class="bk-soft flex flex-col gap-2.5 rounded-2xl px-4 py-4 text-sm">
    <div>
        <p class="font-semibold text-gray-950">{{ $workOrder->work_notes ?: 'Catatan pekerjaan kosong' }}</p>
        <p class="bk-muted mt-0.5 text-xs">Dikerjakan {{ $workOrder->mechanic?->name ?? '–' }}</p>
    </div>

    @if ($items->isEmpty())
        <p class="bk-muted">Tidak ada sparepart terpakai. Total hanya biaya jasa.</p>
    @else
        <div class="flex flex-col gap-1.5 tabular-nums">
            @foreach ($items as $item)
                <div class="flex items-baseline justify-between gap-3">
                    <span class="min-w-0 text-gray-950">
                        {{ $item->sparepart?->name }}
                        <span class="bk-muted">× {{ $item->qty }} @ {{ \App\Support\Format::rupiah($item->unit_price) }}</span>
                    </span>
                    <span class="shrink-0 text-gray-950">{{ \App\Support\Format::rupiah($item->subtotal) }}</span>
                </div>
            @endforeach
        </div>

        <div class="flex items-baseline justify-between gap-3 border-t border-gray-950/10 pt-2.5 font-semibold text-gray-950">
            <span>Subtotal sparepart</span>
            <span class="tabular-nums">{{ \App\Support\Format::rupiah($items->sum('subtotal')) }}</span>
        </div>
    @endif
</div>
