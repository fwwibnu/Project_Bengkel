<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use App\Support\ReportRange;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;

/**
 * Laporan untuk Admin & Pemilik (hanya baca):
 * Pendapatan (harian/bulanan), Transaksi Servis, Sirkulasi Stok.
 * Cetak / simpan PDF lewat halaman cetak (route laporan.cetak).
 */
class Laporan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan Operasional';

    protected static ?string $slug = 'laporan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.laporan';

    #[Url]
    public string $tab = 'pendapatan';

    #[Url]
    public ?string $dari = null;

    #[Url]
    public ?string $sampai = null;

    #[Url]
    public string $periode = 'daily';

    public static function canAccess(): bool
    {
        return Gate::allows('lihat-laporan');
    }

    public function mount(): void
    {
        $this->tab = ReportRange::jenis($this->tab);
        $this->periode = ReportRange::periode($this->periode);
        $this->dari ??= now()->startOfMonth()->toDateString();
        $this->sampai ??= now()->toDateString();
    }

    public function getSubheading(): ?string
    {
        return auth()->user()?->isPemilik()
            ? 'Mode lihat saja. Gunakan tombol Cetak untuk menyimpan sebagai PDF.'
            : 'Pilih jenis laporan dan rentang tanggal. Gunakan tombol Cetak untuk menyimpan sebagai PDF.';
    }

    public function setTab(string $tab): void
    {
        $this->tab = ReportRange::jenis($tab);
    }

    /** Pintasan rentang tanggal. */
    public function preset(string $preset): void
    {
        [$from, $to] = match ($preset) {
            'hari-ini' => [now(), now()],
            'bulan-lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            '6-bulan' => [now()->subMonthsNoOverflow(5)->startOfMonth(), now()],
            default => [now()->startOfMonth(), now()],
        };

        $this->dari = $from->toDateString();
        $this->sampai = $to->toDateString();

        if ($preset === '6-bulan') {
            $this->periode = 'monthly';
        }
    }

    public function getCetakUrl(): string
    {
        return route('laporan.cetak', [
            'jenis' => $this->tab,
            'dari' => $this->dari,
            'sampai' => $this->sampai,
            'periode' => $this->periode,
        ]);
    }

    protected function getViewData(): array
    {
        [$from, $to] = ReportRange::dates($this->dari, $this->sampai);
        $jenis = ReportRange::jenis($this->tab);
        $periode = ReportRange::periode($this->periode);
        $report = app(ReportService::class);

        return [
            'jenis' => $jenis,
            'periode' => $periode,
            'rangeLabel' => ReportRange::label($from, $to),
            'pendapatan' => $jenis === 'pendapatan' ? [
                'summary' => $report->revenueSummary($from, $to),
                'rows' => $report->revenueGrouped($from, $to, $periode),
            ] : null,
            'servis' => $jenis === 'servis' ? $report->serviceRecap($from, $to) : null,
            'stok' => $jenis === 'stok' ? $report->stockCirculation($from, $to) : null,
        ];
    }
}
