<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sparepart;
use App\Models\WorkOrder;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran otomatis:
 * - Work Order : WO-YYYYMMDD-NNN  (urut per hari)
 * - Pembayaran : TRX-YYYYMMDD-NNN (urut per hari, tampil di nota)
 * - Sparepart  : SP-NNN           (kalau kode dikosongkan)
 *
 * Dipanggil DI DALAM DB::transaction. Baris yang dibaca dikunci (lockForUpdate),
 * dan kolom nomor punya index UNIQUE. Kalau tetap bentrok (dua orang menyimpan
 * di detik yang sama), pakai NumberGenerator::retry() supaya transaksi diulang.
 */
class NumberGenerator
{
    public static function workOrder(?CarbonInterface $at = null): string
    {
        return static::daily(WorkOrder::class, 'WO', $at);
    }

    public static function payment(?CarbonInterface $at = null): string
    {
        return static::daily(Payment::class, 'TRX', $at);
    }

    public static function sparepartCode(): string
    {
        $max = Sparepart::query()
            ->where('code', 'like', 'SP-%')
            ->lockForUpdate()
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, 3))
            ->max();

        return 'SP-'.str_pad((string) (((int) $max) + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Jalankan $callback di dalam DB::transaction, ulangi maksimal 3 kali
     * kalau gagal karena nomor unik bentrok.
     *
     * @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function retry(Closure $callback, int $attempts = 3): mixed
    {
        for ($i = 1; ; $i++) {
            try {
                return DB::transaction($callback);
            } catch (UniqueConstraintViolationException $e) {
                if ($i >= $attempts) {
                    throw $e;
                }
            }
        }
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    protected static function daily(string $model, string $prefix, ?CarbonInterface $at): string
    {
        $at ??= now();
        $base = $prefix.'-'.$at->format('Ymd').'-';

        $max = $model::query()
            ->where('number', 'like', $base.'%')
            ->lockForUpdate()
            ->pluck('number')
            ->map(fn (string $number) => (int) substr($number, strlen($base)))
            ->max();

        return $base.str_pad((string) (((int) $max) + 1), 3, '0', STR_PAD_LEFT);
    }
}
