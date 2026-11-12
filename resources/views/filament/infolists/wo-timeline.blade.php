{{-- Timeline status Work Order: Baru → Sedang Dikerjakan → Selesai → Dibayar --}}
@php
    $wo = $getRecord();
    $order = [\App\Enums\WorkOrderStatus::Baru, \App\Enums\WorkOrderStatus::Dikerjakan, \App\Enums\WorkOrderStatus::Selesai, \App\Enums\WorkOrderStatus::Dibayar];
    $currentIndex = array_search($wo->status, $order, true);

    $steps = [
        ['status' => \App\Enums\WorkOrderStatus::Baru, 'time' => $wo->created_at, 'note' => 'ditugaskan ke ' . ($wo->mechanic?->name ?? 'mekanik')],
        ['status' => \App\Enums\WorkOrderStatus::Dikerjakan, 'time' => $wo->started_at, 'note' => 'diubah oleh mekanik'],
        ['status' => \App\Enums\WorkOrderStatus::Selesai, 'time' => $wo->finished_at, 'note' => 'diubah oleh mekanik'],
        ['status' => \App\Enums\WorkOrderStatus::Dibayar, 'time' => $wo->paid_at, 'note' => 'otomatis saat kasir memproses'],
    ];

    $dot = [
        'info' => 'bg-sky-600 ring-sky-100',
        'warning' => 'bg-amber-500 ring-amber-100',
        'success' => 'bg-green-600 ring-green-100',
        'gray' => 'bg-slate-500 ring-slate-200',
    ];
@endphp

<ol class="flex flex-col">
    @foreach ($steps as $i => $step)
        @php $reached = $i <= $currentIndex; @endphp
        <li class="flex gap-3">
            <div class="flex flex-col items-center">
                <span @class([
                    'mt-0.5 h-3.5 w-3.5 shrink-0 rounded-full',
                    $dot[$step['status']->getColor()] . ' ring-4' => $reached,
                    'border-2 border-slate-300 bg-white' => ! $reached,
                ])></span>
                @unless ($loop->last)
                    <span @class(['w-0.5 flex-1 min-h-[28px]', 'bg-slate-300' => $i < $currentIndex, 'bg-slate-200' => $i >= $currentIndex])></span>
                @endunless
            </div>
            <div @class(['flex flex-col', 'pb-4' => ! $loop->last])>
                <span @class(['text-sm', 'font-bold text-gray-950' => $reached, 'font-medium text-gray-500' => ! $reached])>
                    {{ $step['status']->getLabel() }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ $step['time'] ? \App\Support\Format::tanggalJam($step['time']) . ' · ' : '' }}{{ $step['note'] }}
                </span>
            </div>
        </li>
    @endforeach
</ol>
