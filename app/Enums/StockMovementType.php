<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Jenis pergerakan stok:
 * - masuk  : Tambah Stok oleh Admin (termasuk stok awal)
 * - keluar : sparepart dicatat mekanik di Work Order
 * - batal  : sparepart salah catat dihapus mekanik (stok dikembalikan)
 */
enum StockMovementType: string implements HasLabel, HasColor
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Batal = 'batal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Masuk => 'Masuk',
            self::Keluar => 'Keluar',
            self::Batal => 'Batal (dikembalikan)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Masuk => 'success',
            self::Keluar => 'gray',
            self::Batal => 'info',
        };
    }

    /** +1 menambah stok, -1 mengurangi stok. */
    public function arah(): int
    {
        return $this === self::Keluar ? -1 : 1;
    }
}
