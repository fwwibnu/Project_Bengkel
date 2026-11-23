{{-- Kartu kendaraan di daftar Kendaraan (VehicleResource::table). --}}
@php
    /** @var \App\Models\Vehicle $v */
    $v = $getRecord();
    $S = \App\Enums\WorkOrderStatus::class;

    // Relasi workOrders pada query daftar hanya berisi WO aktif (lihat VehicleResource::table)
    $active = $v->workOrders->first();
    $shopLabel = match ($active?->status) {
        $S::Dikerjakan => 'Sedang dikerjakan',
        $S::Baru => 'Antre, belum dikerjakan',
        default => null,
    };

    $type = $v->type instanceof \App\Enums\VehicleType ? $v->type : \App\Enums\VehicleType::tryFrom((string) $v->type);
@endphp

<div class="bk-cv flex h-full flex-col gap-4">
    <div class="flex items-start justify-between gap-3">
        <span class="bk-plate-tag"><span>{{ $v->plate_number }}</span></span>
        <span class="bk-pill bk-soft shrink-0 text-gray-700">{{ $type?->getLabel() ?? '–' }}</span>
    </div>

    <div>
        <p class="truncate text-lg font-bold tracking-tight text-gray-950">{{ trim($v->brand.' '.$v->model) }}</p>
        <p class="bk-muted mt-0.5 truncate text-sm">
            {{ $v->year ? 'Tahun '.$v->year.' · ' : '' }}{{ $v->customer?->name }}
        </p>
    </div>

    <div class="mt-auto flex min-h-[28px] items-center justify-between gap-3 border-t border-gray-950/[0.07] pt-3">
        @if ($shopLabel)
            <span class="bk-pill gap-1.5 bg-amber-100 text-amber-800">
                <span class="bk-pulse-amber h-1.5 w-1.5 rounded-full bg-amber-600"></span>{{ $shopLabel }}
            </span>
        @else
            <span class="bk-muted text-[13px]">
                @if ($v->work_orders_max_created_at)
                    Servis terakhir {{ \App\Support\Format::tanggal($v->work_orders_max_created_at) }}
                @else
                    Belum pernah servis
                @endif
            </span>
        @endif
        <span class="bk-muted shrink-0 text-[13px] tabular-nums">{{ $v->work_orders_count }}× servis</span>
    </div>
</div>
