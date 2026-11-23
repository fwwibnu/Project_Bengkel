{{-- Kartu sparepart di daftar Sparepart (SparepartResource::table). --}}
@php
    /** @var \App\Models\Sparepart $part */
    $part = $getRecord();
    $fmt = \App\Support\Format::class;

    $label = $part->stockLabel() ?? 'Aman';
    [$pillClass, $barClass] = match ($label) {
        'Habis' => ['bg-red-100 text-red-800', 'bg-red-600'],
        'Menipis' => ['bg-amber-100 text-amber-800', 'bg-amber-600'],
        default => ['bg-green-100 text-green-800', 'bg-primary-600'],
    };

    // Bar: penuh = 4× stok minimal; garis tipis menandai batas minimal (25%)
    $cap = max(1, $part->min_stock * 4);
    $pct = min(100, (int) round($part->stock / $cap * 100));
@endphp

<div class="bk-cv flex h-full flex-col gap-3.5">
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="bk-muted text-xs font-semibold">{{ $part->code }}</p>
            <p class="mt-0.5 truncate text-base font-bold tracking-tight text-gray-950">{{ $part->name }}</p>
        </div>
        <span class="bk-pill {{ $pillClass }} shrink-0">{{ $label }}</span>
    </div>

    <div>
        <p class="text-[2rem] font-extrabold leading-none tracking-[-0.04em] tabular-nums text-gray-950">
            {{ $fmt::angka($part->stock) }}
            <span class="bk-muted text-sm font-semibold tracking-normal">unit</span>
        </p>
        <div class="bk-track relative mt-3 h-2 overflow-hidden rounded-full" aria-label="Sisa stok {{ $part->stock }}, minimal {{ $part->min_stock }}">
            <div class="bk-fill h-full rounded-full {{ $barClass }}" style="width: {{ $pct }}%"></div>
            <span class="absolute inset-y-0 left-1/4 w-0.5 bg-white/80"></span>
        </div>
        <div class="relative h-4">
            <span class="bk-muted absolute left-1/4 top-1 -translate-x-1/2 whitespace-nowrap text-[11px]">min {{ $part->min_stock }}</span>
        </div>
    </div>

    <div class="mt-auto flex items-end justify-between gap-3 border-t border-gray-950/[0.07] pt-3">
        <div>
            <p class="text-[15px] font-bold tabular-nums text-gray-950">{{ $fmt::rupiah($part->selling_price) }}</p>
            <p class="bk-muted mt-0.5 text-xs tabular-nums">harga beli {{ $fmt::rupiah($part->purchase_price) }}</p>
        </div>
    </div>
</div>
