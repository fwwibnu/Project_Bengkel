<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Metode bayar. Tidak ada hitung kembalian (di luar lingkup). */
enum PaymentMethod: string implements HasLabel, HasColor
{
    case Tunai = 'tunai';
    case NonTunai = 'non_tunai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tunai => 'Tunai',
            self::NonTunai => 'Non-tunai',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Tunai => 'success',
            self::NonTunai => 'info',
        };
    }
}
