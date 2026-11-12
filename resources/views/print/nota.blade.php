{{-- Nota pembayaran siap cetak (ukuran A5). Data dari NotaController. --}}
@extends('layouts.print')

@php($fmt = \App\Support\Format::class)

@section('title', 'Nota ' . $payment->number)
@section('back-url', $kembaliUrl)
@section('back-label', 'Kembali')

@push('print-style')
    <style>
        @page { size: A5 portrait; margin: 10mm; }
    </style>
@endpush

@section('content')
    <article class="mx-auto w-full max-w-[148mm] rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200 sm:p-8 print:max-w-none print:rounded-none print:p-0 print:shadow-none print:ring-0">
        {{-- Kop bengkel --}}
        <header class="flex items-start justify-between gap-4 border-b-2 border-stone-900 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-bronze-600 print:border print:border-stone-900 print:bg-white">
                    <svg aria-hidden="true" class="h-6 w-6 text-white print:text-stone-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/></svg>
                </div>
                <div>
                    <p class="text-lg font-bold leading-tight">{{ $bengkel['nama_lengkap'] }}</p>
                    <p class="text-xs text-stone-600">{{ $bengkel['alamat'] }}</p>
                    <p class="text-xs text-stone-600">Telp. {{ $bengkel['telepon'] }}</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1.5">
                <h1 class="text-base font-bold tracking-[0.08em]">NOTA</h1>
                <span class="rounded-md border-2 border-green-700 px-3 py-0.5 text-xs font-bold tracking-[0.1em] text-green-700">LUNAS</span>
            </div>
        </header>

        {{-- Info transaksi --}}
        <section class="grid grid-cols-2 gap-x-6 gap-y-3 py-4 text-sm">
            <div><p class="text-xs text-stone-500">No. transaksi</p><p class="font-bold">{{ $payment->number }}</p></div>
            <div><p class="text-xs text-stone-500">Tanggal</p><p class="font-semibold">{{ $fmt::tanggalJam($payment->paid_at) }}</p></div>
            <div><p class="text-xs text-stone-500">Pelanggan</p><p class="font-semibold">{{ $workOrder->customer?->name }}</p></div>
            <div><p class="text-xs text-stone-500">Kendaraan</p><p class="font-semibold">{{ $workOrder->vehicle?->getLabel() }}</p></div>
            <div><p class="text-xs text-stone-500">No. Work Order</p><p class="font-semibold">{{ $workOrder->number }}</p></div>
            <div><p class="text-xs text-stone-500">Mekanik / Kasir</p><p class="font-semibold">{{ $workOrder->mechanic?->name }} / {{ $payment->cashier?->name ?? '–' }}</p></div>
        </section>

        {{-- Rincian --}}
        <table class="w-full text-sm tabular-nums">
            <thead>
                <tr class="border-b border-stone-900 text-left">
                    <th class="py-2 font-bold">Rincian</th>
                    <th class="w-12 py-2 text-right font-bold">Qty</th>
                    <th class="w-24 py-2 text-right font-bold">Harga</th>
                    <th class="w-28 py-2 text-right font-bold">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-stone-200">
                    <td class="py-2.5">
                        <p class="font-semibold">Jasa servis</p>
                        @if ($workOrder->work_notes)
                            <p class="text-xs text-stone-500">{{ $workOrder->work_notes }}</p>
                        @endif
                    </td>
                    <td class="py-2.5 text-right">1</td>
                    <td class="py-2.5 text-right">{{ $fmt::angka($payment->service_fee) }}</td>
                    <td class="py-2.5 text-right">{{ $fmt::angka($payment->service_fee) }}</td>
                </tr>
                @foreach ($workOrder->items as $item)
                    <tr class="border-b border-stone-200">
                        <td class="py-2.5">{{ $item->sparepart?->name }}</td>
                        <td class="py-2.5 text-right">{{ $item->qty }}</td>
                        <td class="py-2.5 text-right">{{ $fmt::angka($item->unit_price) }}</td>
                        <td class="py-2.5 text-right">{{ $fmt::angka($item->subtotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Total --}}
        <section class="ml-auto mt-4 w-full max-w-[260px] space-y-1.5 text-sm tabular-nums">
            <div class="flex justify-between"><span class="text-stone-500">Jasa</span><span>{{ $fmt::rupiah($payment->service_fee) }}</span></div>
            <div class="flex justify-between"><span class="text-stone-500">Sparepart</span><span>{{ $fmt::rupiah($payment->sparepart_total) }}</span></div>
            <div class="flex items-baseline justify-between border-t-2 border-stone-900 pt-2">
                <span class="font-bold">TOTAL</span>
                <span class="text-xl font-bold">{{ $fmt::rupiah($payment->grand_total) }}</span>
            </div>
            <div class="flex justify-between"><span class="text-stone-500">Metode</span><span class="font-semibold">{{ $payment->method->getLabel() }}</span></div>
        </section>

        <footer class="mt-8 flex items-end justify-between gap-4 border-t border-dashed border-stone-400 pt-4 text-xs text-stone-600">
            <p class="leading-relaxed">Terima kasih telah servis di {{ $bengkel['nama'] }}.<br>Simpan nota ini sebagai bukti pembayaran.</p>
            <div class="flex flex-col items-center gap-10 text-center">
                <span>Kasir</span>
                <span class="font-semibold text-stone-900">{{ $payment->cashier?->name ?? '..................' }}</span>
            </div>
        </footer>
    </article>
@endsection
