{{-- Sirkulasi stok: masuk (Tambah Stok) dan keluar (pemakaian WO, dikurangi yang dibatalkan). Variabel: $data, $withMovements --}}
<div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
    <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-semibold text-gray-500">Total stok masuk</p>
        <p class="mt-1 text-xl font-bold tabular-nums text-green-700">+{{ \App\Support\Format::angka($data['masuk']) }} <span class="text-sm font-medium text-gray-500">unit</span></p>
    </div>
    <div class="bk-stat rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-semibold text-gray-500">Total stok keluar</p>
        <p class="mt-1 text-xl font-bold tabular-nums text-gray-950">−{{ \App\Support\Format::angka($data['keluar']) }} <span class="text-sm font-medium text-gray-500">unit</span></p>
    </div>
    <div class="bk-stat col-span-2 rounded-xl border border-gray-200 bg-white p-4 lg:col-span-1">
        <p class="text-xs font-semibold text-gray-500">Sparepart menipis (akhir periode)</p>
        <p class="mt-1 text-xl font-bold tabular-nums text-amber-700">{{ $data['rows']->filter(fn ($r) => $r['akhir'] <= $r['min_stock'])->count() }} <span class="text-sm font-medium text-gray-500">item</span></p>
    </div>
</div>

<div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="w-full min-w-[560px] text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500">
            <tr>
                <th class="px-4 py-3">Sparepart</th>
                <th class="px-4 py-3 text-right">Awal</th>
                <th class="px-4 py-3 text-right">Masuk</th>
                <th class="px-4 py-3 text-right">Keluar</th>
                <th class="px-4 py-3 text-right">Akhir</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 tabular-nums">
            @forelse ($data['rows'] as $row)
                <tr>
                    <td class="px-4 py-2.5">
                        <span class="font-semibold text-gray-950">{{ $row['name'] }}</span>
                        <span class="block text-xs text-gray-500">{{ $row['code'] }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-right">{{ $row['awal'] }}</td>
                    <td @class(['px-4 py-2.5 text-right', 'font-semibold text-green-700' => $row['masuk'] > 0, 'text-gray-400' => $row['masuk'] === 0])>
                        {{ $row['masuk'] > 0 ? '+' . $row['masuk'] : 0 }}
                    </td>
                    <td class="px-4 py-2.5 text-right">{{ $row['keluar'] }}</td>
                    <td @class([
                        'px-4 py-2.5 text-right font-bold',
                        'text-red-700' => $row['akhir'] <= 0,
                        'text-amber-700' => $row['akhir'] > 0 && $row['akhir'] <= $row['min_stock'],
                        'text-gray-950' => $row['akhir'] > $row['min_stock'],
                    ])>{{ $row['akhir'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data sparepart.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($withMovements ?? true)
    <div class="mt-4 rounded-xl border border-gray-200 bg-white">
        <div class="px-4 pt-4">
            <p class="text-sm font-bold text-gray-950">Pergerakan stok</p>
            <p class="text-xs text-gray-500">50 pergerakan terakhir pada rentang tanggal ini</p>
        </div>
        <ul class="mt-2 divide-y divide-gray-100">
            @forelse ($data['movements'] as $m)
                <li class="flex gap-3 px-4 py-2.5">
                    <span class="bk-pill bk-pill-mv-{{ $m->type->value }} self-start">{{ $m->type === \App\Enums\StockMovementType::Batal ? 'Batal' : $m->type->getLabel() }}</span>
                    <div class="min-w-0 text-sm">
                        <p class="font-semibold text-gray-950">{{ $m->sparepart?->name }} ×{{ $m->qty }}</p>
                        <p class="text-xs text-gray-500">
                            {{ \App\Support\Format::tanggalJam($m->created_at) }}
                            @if ($m->reference) · {{ $m->reference }} @endif
                            @if ($m->note) · {{ $m->note }} @endif
                            @if ($m->user) · {{ $m->user->name }} @endif
                        </p>
                    </div>
                </li>
            @empty
                <li class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada pergerakan stok pada rentang tanggal ini.</li>
            @endforelse
        </ul>
    </div>
@endif
