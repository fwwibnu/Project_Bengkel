{{-- Rincian sparepart di modal Proses Bayar (Kasir). Harga = harga terkunci saat mekanik mencatat. --}}
@php($items = $workOrder->items)

<div class="space-y-3">
    <div class="rounded-lg bg-gray-50 p-3 text-sm">
        <span class="block text-xs font-semibold text-gray-500">Pekerjaan</span>
        <span class="text-gray-950">{{ $workOrder->work_notes ?: '–' }}</span>
        <span class="mt-1 block text-xs text-gray-500">Dikerjakan {{ $workOrder->mechanic?->name }}</span>
    </div>

    @if ($items->isEmpty())
        <p class="text-sm text-gray-500">Tidak ada sparepart terpakai. Total hanya biaya jasa.</p>
    @else
        <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
            <table class="w-full min-w-[420px] text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
                    <tr>
                        <th class="px-3 py-2">Sparepart</th>
                        <th class="px-3 py-2 text-right">Jumlah</th>
                        <th class="px-3 py-2 text-right">Harga</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums">
                    @foreach ($items as $item)
                        <tr>
                            <td class="px-3 py-2 font-semibold text-gray-950">{{ $item->sparepart?->name }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->qty }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Format::rupiah($item->unit_price) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\Format::rupiah($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-gray-200 bg-gray-50">
                        <td colspan="3" class="px-3 py-2 font-semibold">Subtotal sparepart</td>
                        <td class="px-3 py-2 text-right font-bold tabular-nums">{{ \App\Support\Format::rupiah($items->sum('subtotal')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
