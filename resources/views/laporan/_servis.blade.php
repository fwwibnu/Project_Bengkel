{{-- Rekap transaksi servis (Work Order dibuat pada rentang tanggal). Variabel: $data --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
    <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-semibold text-gray-500">Total Work Order</p>
        <p class="mt-1 text-xl font-bold tabular-nums text-gray-950">{{ $data['total'] }}</p>
    </div>
    @foreach (\App\Enums\WorkOrderStatus::cases() as $status)
        <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold text-gray-500">{{ $status->getLabel() }}</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-gray-950">{{ $data['perStatus'][$status->value] ?? 0 }}</p>
        </div>
    @endforeach
    <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-semibold text-gray-500">Nilai dibayar</p>
        <p class="mt-1 text-lg font-bold tabular-nums text-gray-950">{{ \App\Support\Format::rupiah($data['paidTotal']) }}</p>
    </div>
</div>

<div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="w-full min-w-[760px] text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
            <tr>
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">No. WO</th>
                <th class="px-4 py-3">Kendaraan</th>
                <th class="px-4 py-3">Pelanggan</th>
                <th class="px-4 py-3">Mekanik</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($data['rows'] as $wo)
                <tr>
                    <td class="px-4 py-2.5 whitespace-nowrap">{{ \App\Support\Format::tanggal($wo->created_at) }}</td>
                    <td class="px-4 py-2.5 font-semibold whitespace-nowrap text-gray-950">{{ $wo->number }}</td>
                    <td class="px-4 py-2.5">
                        <span class="font-semibold text-gray-950">{{ $wo->vehicle?->plate_number }}</span>
                        <span class="block text-xs text-gray-500">{{ trim(($wo->vehicle?->brand ?? '') . ' ' . ($wo->vehicle?->model ?? '')) }}</span>
                    </td>
                    <td class="px-4 py-2.5">{{ $wo->customer?->name }}</td>
                    <td class="px-4 py-2.5">{{ $wo->mechanic?->name }}</td>
                    <td class="px-4 py-2.5"><span class="bk-pill bk-pill-{{ $wo->status->value }}">{{ $wo->status->getLabel() }}</span></td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                        {{ $wo->payment ? \App\Support\Format::rupiah($wo->payment->grand_total) : '–' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada Work Order pada rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
