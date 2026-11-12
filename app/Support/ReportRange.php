<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * Membaca filter laporan (jenis, rentang tanggal, periode) dari input pengguna dengan aman.
 * Dipakai halaman Laporan (Filament) dan halaman cetak laporan (controller) supaya hasilnya sama.
 */
class ReportRange
{
    public const JENIS = ['pendapatan', 'servis', 'stok'];

    public const PERIODE = ['daily', 'monthly'];

    public static function jenis(?string $jenis): string
    {
        return in_array($jenis, self::JENIS, true) ? $jenis : 'pendapatan';
    }

    public static function periode(?string $periode): string
    {
        return in_array($periode, self::PERIODE, true) ? $periode : 'daily';
    }

    /**
     * @return array{0: Carbon, 1: Carbon} [dari, sampai] — default: awal bulan ini s/d hari ini.
     */
    public static function dates(?string $dari, ?string $sampai): array
    {
        $from = static::parse($dari) ?? now()->startOfMonth();
        $to = static::parse($sampai) ?? now();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        // Batasi maksimal 2 tahun supaya laporan tetap ringan
        if ($from->diffInDays($to) > 731) {
            $from = $to->copy()->subYears(2);
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    public static function label(Carbon $from, Carbon $to): string
    {
        return Format::tanggal($from).' – '.Format::tanggal($to);
    }

    protected static function parse(?string $value): ?Carbon
    {
        if (blank($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
