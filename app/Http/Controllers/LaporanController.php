<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Support\ReportRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Laporan siap cetak / simpan PDF dari browser. Admin & Pemilik. */
class LaporanController extends Controller
{
    public function cetak(Request $request, ReportService $report): View
    {
        Gate::authorize('lihat-laporan');

        $q = fn (string $key): ?string => is_string($request->query($key)) ? $request->query($key) : null;

        [$from, $to] = ReportRange::dates($q('dari'), $q('sampai'));
        $jenis = ReportRange::jenis($q('jenis'));
        $periode = ReportRange::periode($q('periode'));

        return view('print.laporan', [
            'jenis' => $jenis,
            'periode' => $periode,
            'rangeLabel' => ReportRange::label($from, $to),
            'bengkel' => config('bengkel'),
            'dicetakOleh' => $request->user(),
            'pendapatan' => $jenis === 'pendapatan' ? [
                'summary' => $report->revenueSummary($from, $to),
                'rows' => $report->revenueGrouped($from, $to, $periode),
            ] : null,
            'servis' => $jenis === 'servis' ? $report->serviceRecap($from, $to) : null,
            'stok' => $jenis === 'stok' ? $report->stockCirculation($from, $to) : null,
        ]);
    }
}
