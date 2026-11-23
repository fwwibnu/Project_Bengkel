{{-- Kartu mekanik di daftar Mekanik (MechanicResource::table). --}}
@php
    /** @var \App\Models\Mechanic $m */
    $m = $getRecord();
    $MS = \App\Enums\MechanicStatus::class;
    $WS = \App\Enums\WorkOrderStatus::class;

    $initials = \Illuminate\Support\Str::of($m->name)
        ->explode(' ')->filter()->take(2)
        ->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->join('');

    $isLibur = $m->status === $MS::Libur;
    [$pillClass, $dotClass] = match ($m->status) {
        $MS::Tersedia => ['bg-green-100 text-green-800', 'bk-pulse bg-green-600'],
        $MS::Sibuk => ['bg-amber-100 text-amber-800', 'bk-pulse-amber bg-amber-600'],
        default => ['bk-soft text-gray-600', 'bg-gray-400'],
    };

    $wo = $m->activeWorkOrders->sortBy(fn ($w) => $w->status === $WS::Dikerjakan ? 0 : 1)->first();
@endphp

<div class="bk-cv flex h-full flex-col gap-4">
    <div class="flex items-center gap-4">
        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-extrabold {{ $isLibur ? 'bk-soft bk-muted' : 'bg-primary-600/10 text-primary-600' }}">{{ $initials }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-xl font-extrabold tracking-tight text-gray-950">{{ $m->name }}</p>
            <p class="bk-muted truncate text-sm">{{ $m->specialty ?: 'Keahlian belum diisi' }}</p>
        </div>
        <span class="bk-pill {{ $pillClass }} shrink-0 gap-2 px-3 py-1 text-[13px]">
            <span class="h-2 w-2 rounded-full {{ $dotClass }}"></span>{{ $m->status->getLabel() }}
        </span>
    </div>

    @if ($wo)
        <div class="rounded-2xl border border-primary-600/20 bg-primary-50/60 px-4 py-3">
            <p class="bk-muted text-xs font-semibold">Sedang memegang · {{ $m->activeWorkOrders->count() }} Work Order</p>
            <p class="mt-1 text-[15px] font-bold text-gray-950">{{ $wo->number }} · {{ $wo->vehicle?->plate_number }}</p>
            <p class="bk-muted mt-0.5 text-[13px]">
                {{ trim(($wo->vehicle?->brand ?? '').' '.($wo->vehicle?->model ?? '')) }} · {{ $wo->status->getLabel() }} · {{ $wo->customer?->name }}
            </p>
        </div>
    @else
        <div class="rounded-2xl border border-gray-950/5 bg-[#F7F5F0] px-4 py-3">
            <p class="bk-muted text-xs font-semibold">Hari ini</p>
            <p class="mt-1 text-[15px] font-bold text-gray-950">{{ $isLibur ? 'Libur' : 'Tidak memegang Work Order' }}</p>
            <p class="bk-muted mt-0.5 text-[13px]">
                {{ $isLibur ? 'Tidak muncul di pilihan saat membuat Work Order.' : 'Siap ditugaskan oleh Admin.' }}
            </p>
        </div>
    @endif

    <div class="mt-auto flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-t border-gray-950/[0.07] pt-3 text-[13px]">
        <span class="bk-muted flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-m-device-phone-mobile" class="h-4 w-4" />{{ $m->phone ?: 'No. HP belum diisi' }}
        </span>
        <span class="bk-muted flex items-center gap-1.5">
            <x-filament::icon icon="heroicon-m-user-circle" class="h-4 w-4" />
            @if ($m->user)
                Akun <span class="font-semibold text-gray-950">{{ $m->user->username }}</span>
            @else
                Belum terhubung ke akun
            @endif
        </span>
        <span class="bk-muted">{{ $m->finished_month }} selesai bulan ini</span>
    </div>
</div>
