{{-- Daftar sparepart terpakai di detail Work Order (Admin) --}}
@php
    $workOrder = $getRecord();
    $items = $workOrder->items()->with('sparepart')->get();
@endphp

@if ($items->isEmpty())
    <div class="flex flex-col items-center gap-2 py-6 text-center">
        <x-filament::icon icon="heroicon-o-cube" class="h-7 w-7 text-gray-400" />
        <p class="text-sm font-semibold text-gray-950">Belum ada sparepart dicatat</p>
        <p class="text-sm text-gray-500">Mekanik akan mencatatnya saat mengerjakan servis.</p>
    </div>
@else
    <div class="-mx-2 overflow-x-auto">
        <table class="w-full min-w-[480px] text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold text-gray-500">
                    <th class="px-2 py-2">Sparepart</th>
                    <th class="px-2 py-2 text-right">Jumlah</th>
                    <th class="px-2 py-2 text-right">Harga (terkunci)</th>
                    <th class="px-2 py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 tabular-nums">
                @foreach ($items as $item)
                    <tr>
                        <td class="px-2 py-2.5">
                            <span class="font-semibold text-gray-950">{{ $item->sparepart?->name }}</span>
                            <span class="block text-xs text-gray-500">{{ $item->sparepart?->code }}</span>
                        </td>
                        <td class="px-2 py-2.5 text-right">{{ $item->qty }}</td>
                        <td class="px-2 py-2.5 text-right">{{ \App\Support\Format::rupiah($item->unit_price) }}</td>
                        <td class="px-2 py-2.5 text-right">{{ \App\Support\Format::rupiah($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-gray-200">
                    <td colspan="3" class="px-2 py-2.5 font-semibold text-gray-950">Subtotal sparepart</td>
                    <td class="px-2 py-2.5 text-right font-bold tabular-nums text-gray-950">{{ \App\Support\Format::rupiah($items->sum('subtotal')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
