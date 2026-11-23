{{-- Kartu pelanggan di daftar Pelanggan (CustomerResource::table). --}}
@php
    /** @var \App\Models\Customer $c */
    $c = $getRecord();
    $initials = \Illuminate\Support\Str::of($c->name)
        ->explode(' ')->filter()->take(2)
        ->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->join('');
    $last = $c->work_orders_max_created_at;
@endphp

<div class="bk-cv flex h-full flex-col gap-4">
    <div class="flex items-center gap-3.5">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-600/10 text-base font-extrabold text-primary-600">{{ $initials }}</span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[17px] font-bold tracking-tight text-gray-950">{{ $c->name }}</p>
            <p class="bk-muted text-[13px]">{{ $c->vehicles->count() }} kendaraan</p>
        </div>
        @if ($c->in_shop)
            <span class="bk-pill shrink-0 gap-1.5 bg-amber-100 text-amber-800">
                <span class="bk-pulse-amber h-1.5 w-1.5 rounded-full bg-amber-600"></span>Di bengkel
            </span>
        @endif
    </div>

    <div class="flex flex-col gap-1.5 text-sm">
        <span class="flex items-center gap-2.5 text-gray-950">
            <x-filament::icon icon="heroicon-m-device-phone-mobile" class="bk-muted h-4 w-4 shrink-0" />
            {{ $c->phone }}
        </span>
        <span class="bk-muted flex items-start gap-2.5">
            <x-filament::icon icon="heroicon-m-map-pin" class="mt-0.5 h-4 w-4 shrink-0" />
            <span class="line-clamp-2">{{ $c->address ?: 'Alamat belum diisi' }}</span>
        </span>
    </div>

    <div class="mt-auto flex flex-wrap gap-2 border-t border-gray-950/[0.07] pt-3.5">
        @forelse ($c->vehicles as $v)
            <span class="bk-chip inline-flex items-center gap-2 rounded-xl border border-gray-950/10 bg-white px-3 py-1.5">
                <span class="text-[13px] font-bold text-gray-950">{{ $v->plate_number }}</span>
                <span class="bk-muted text-[13px]">{{ trim($v->brand.' '.$v->model) }}</span>
            </span>
        @empty
            <span class="bk-muted text-[13px]">Belum ada kendaraan</span>
        @endforelse
    </div>

    <p class="bk-muted -mt-1 text-xs">
        {{ $last ? 'Servis terakhir '.\App\Support\Format::tanggal($last) : 'Belum pernah servis' }}
    </p>
</div>
