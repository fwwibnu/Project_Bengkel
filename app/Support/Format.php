<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Format tampilan: Rupiah "Rp 125.000" dan tanggal "09 Nov 2026, 14.20". */
class Format
{
    public static function rupiah(int|float|string|null $amount): string
    {
        return 'Rp '.number_format((int) $amount, 0, ',', '.');
    }

    public static function angka(int|float|string|null $value): string
    {
        return number_format((int) $value, 0, ',', '.');
    }

    public static function tanggalJam(CarbonInterface|string|null $date): string
    {
        if (blank($date)) {
            return '–';
        }

        return Carbon::parse($date)->translatedFormat('d M Y, H.i');
    }

    public static function tanggal(CarbonInterface|string|null $date): string
    {
        if (blank($date)) {
            return '–';
        }

        return Carbon::parse($date)->translatedFormat('d M Y');
    }

    public static function hariTanggal(CarbonInterface|string|null $date): string
    {
        if (blank($date)) {
            return '–';
        }

        return Carbon::parse($date)->translatedFormat('l, d M Y');
    }
}
