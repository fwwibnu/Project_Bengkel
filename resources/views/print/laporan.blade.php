{{-- Laporan siap cetak / simpan PDF (A4). Data dari LaporanController. --}}
@extends('layouts.print')

@php
    $judul = match ($jenis) {
        'servis' => 'Laporan Transaksi Servis',
        'stok' => 'Laporan Sirkulasi Stok',
        default => 'Laporan Pendapatan ' . ($periode === 'monthly' ? 'Bulanan' : 'Harian'),
    };
@endphp

@section('title', $judul)
@section('back-url', url('/app/laporan?tab=' . $jenis))
@section('back-label', 'Kembali ke Laporan')

@push('print-style')
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        @media print { .bk-stat { break-inside: avoid; } tr { break-inside: avoid; } }
    </style>
@endpush

@section('content')
    <article class="mx-auto w-full max-w-4xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8 print:max-w-none print:rounded-none print:p-0 print:shadow-none print:ring-0">
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b-2 border-slate-900 pb-4">
            <div>
                <p class="text-lg font-bold">{{ $bengkel['nama_lengkap'] }}</p>
                <p class="text-xs text-slate-600">{{ $bengkel['alamat'] }} · Telp. {{ $bengkel['telepon'] }}</p>
            </div>
            <div class="text-right">
                <h1 class="text-base font-bold">{{ $judul }}</h1>
                <p class="text-sm text-slate-600">Periode {{ $rangeLabel }}</p>
            </div>
        </header>

        @if ($jenis === 'pendapatan')
            @include('laporan._pendapatan', ['data' => $pendapatan, 'periode' => $periode])
        @elseif ($jenis === 'servis')
            @include('laporan._servis', ['data' => $servis])
        @else
            @include('laporan._stok', ['data' => $stok, 'withMovements' => true])
        @endif

        <footer class="mt-8 text-xs text-slate-500">
            Dicetak {{ \App\Support\Format::tanggalJam(now()) }} oleh {{ $dicetakOleh?->name }} ({{ $dicetakOleh?->role?->getLabel() }}).
        </footer>
    </article>
@endsection
