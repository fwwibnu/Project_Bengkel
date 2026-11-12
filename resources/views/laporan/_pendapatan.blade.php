{{-- Laporan pendapatan. Dipakai di halaman Laporan (panel) dan halaman cetak. Variabel: $data, $periode --}}
@php($s = $data['summary'])

<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach ([
        ['Total pendapatan', \App\Support\Format::rupiah($s['total'])],
        ['Jumlah transaksi', \App\Support\Format::angka($s['count'])],
        ['Dari jasa', \App\Support\Format::rupiah($s['fee'])],
        ['Dari sparepart', \App\Support\Format::rupiah($s['parts'])],
    ] as [$label, $value])
        <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs font-semibold text-gray-500">{{ $label }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-gray-950 sm:text-xl">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="w-full min-w-[560px] text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
            <tr>
                <th class="px-4 py-3">{{ $periode === 'monthly' ? 'Bulan' : 'Tanggal' }}</th>
                <th class="px-4 py-3 text-right">Transaksi</th>
                <th class="px-4 py-3 text-right">Jasa</th>
                <th class="px-4 py-3 text-right">Sparepart</th>
                <th class="px-4 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 tabular-nums">
            @forelse ($data['rows'] as $row)
                <tr>
                    <td class="px-4 py-2.5 text-gray-950">{{ $row['label'] }}</td>
                    <td class="px-4 py-2.5 text-right">{{ $row['count'] }}</td>
                    <td class="px-4 py-2.5 text-right">{{ \App\Support\Format::rupiah($row['fee']) }}</td>
                    <td class="px-4 py-2.5 text-right">{{ \App\Support\Format::rupiah($row['parts']) }}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-gray-950">{{ \App\Support\Format::rupiah($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada pembayaran pada rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($data['rows']->isNotEmpty())
            <tfoot>
                <tr class="border-t-2 border-gray-300 bg-bronze-50 font-bold text-gray-950 tabular-nums">
                    <td class="px-4 py-3">Total</td>
                    <td class="px-4 py-3 text-right">{{ $s['count'] }}</td>
                    <td class="px-4 py-3 text-right">{{ \App\Support\Format::rupiah($s['fee']) }}</td>
                    <td class="px-4 py-3 text-right">{{ \App\Support\Format::rupiah($s['parts']) }}</td>
                    <td class="px-4 py-3 text-right">{{ \App\Support\Format::rupiah($s['total']) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
