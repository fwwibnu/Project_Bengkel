{{-- Kartu Work Order di daftar Work Order (WorkOrderResource::table). --}}
@php
    /** @var \App\Models\WorkOrder $wo */
    $wo = $getRecord();
    $S = \App\Enums\WorkOrderStatus::class;
    $fmt = \App\Support\Format::class;

    $steps = [$S::Baru, $S::Dikerjakan, $S::Selesai, $S::Dibayar];
    $stepLabels = ['Baru', 'Dikerjakan', 'Selesai', 'Dibayar'];
    $idx = array_search($wo->status, $steps, true);
    $idx = $idx === false ? 0 : $idx;

    $isLive = $wo->status === $S::Dikerjakan;
    $isDone = in_array($wo->status, [$S::Selesai, $S::Dibayar], true);

    // Baris teks: keluhan saat belum selesai, pekerjaan saat sudah selesai
    $text = $isDone && filled($wo->work_notes) ? $wo->work_notes : $wo->complaint;
    $itemsText = $wo->items
        ->map(fn ($i) => ($i->sparepart?->name ?? 'Sparepart').' ×'.$i->qty)
        ->join(', ');

    if ($wo->status === $S::Dibayar && $wo->payment) {
        $amountLabel = 'Total dibayar';
        $amount = $fmt::rupiah($wo->payment->grand_total);
    } elseif ($wo->status === $S::Selesai) {
        $amountLabel = 'Menunggu kasir';
        $amount = $fmt::rupiah($wo->parts_total ?? 0);
    } else {
        $amountLabel = 'Sparepart';
        $amount = $fmt::rupiah($wo->parts_total ?? 0);
    }

    $initials = \Illuminate\Support\Str::of($wo->mechanic?->name ?? '?')
        ->explode(' ')->filter()->take(2)
        ->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->join('');
@endphp

<div class="bk-cv flex h-full flex-col gap-3.5">
    <div class="flex items-center justify-between gap-2">
        <span class="bk-muted min-w-0 truncate text-xs font-semibold">{{ $wo->number }} · {{ $fmt::tanggal($wo->created_at) }}</span>
        <span class="bk-pill bk-pill-{{ $wo->status->value }} shrink-0 gap-1.5">
            @if ($isLive)
                <span class="bk-pulse-amber h-1.5 w-1.5 rounded-full bg-amber-600"></span>
            @endif
            {{ $wo->status->getLabel() }}
        </span>
    </div>

    <div>
        <p class="text-2xl font-extrabold tracking-[-0.03em] text-gray-950">{{ $wo->vehicle?->plate_number }}</p>
        <p class="bk-muted mt-0.5 text-sm">
            {{ trim(($wo->vehicle?->brand ?? '').' '.($wo->vehicle?->model ?? '')) }} · {{ $wo->customer?->name }}
        </p>
    </div>

    <p class="line-clamp-2 min-h-[2.6rem] text-sm leading-snug text-gray-950">
        @if (! $isDone)
            <span class="bk-muted">Keluhan:</span>
        @endif
        {{ $text }}
        @if ($itemsText !== '')
            <span class="bk-muted">· {{ $itemsText }}</span>
        @endif
    </p>

    {{-- Progres status --}}
    <div aria-label="Progres: {{ $wo->status->getLabel() }}">
        <div class="bk-track relative h-1 overflow-hidden rounded-full">
            <div class="bk-fill absolute inset-y-0 left-0 rounded-full bg-primary-600" style="width: {{ round($idx / 3 * 100) }}%"></div>
        </div>
        <div class="mt-2 flex justify-between text-[11px]">
            @foreach ($stepLabels as $k => $label)
                <span class="{{ $k === $idx ? 'font-bold text-gray-950' : ($k < $idx ? 'font-medium text-gray-950' : 'bk-muted font-medium') }}">{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <div class="mt-auto flex items-center justify-between gap-3 border-t border-gray-950/[0.07] pt-3">
        <span class="flex min-w-0 items-center gap-2 text-[13px] text-gray-950">
            <span class="bk-soft flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold">{{ $initials }}</span>
            <span class="truncate">{{ $wo->mechanic?->name ?? 'Belum ditugaskan' }}</span>
        </span>
        <span class="shrink-0 text-right">
            <span class="bk-muted block text-[11px] font-semibold">{{ $amountLabel }}</span>
            <span class="text-base font-bold tabular-nums tracking-tight text-gray-950">{{ $amount }}</span>
        </span>
    </div>
</div>
