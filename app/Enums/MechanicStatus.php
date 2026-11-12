<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status mekanik. Sibuk/Tersedia diatur otomatis dari Work Order,
 * Libur diatur Admin (App\Services\MechanicService).
 */
enum MechanicStatus: string implements HasLabel, HasColor
{
    case Tersedia = 'tersedia';
    case Sibuk = 'sibuk';
    case Libur = 'libur';

    public function getLabel(): string
    {
        return match ($this) {
            self::Tersedia => 'Tersedia',
            self::Sibuk => 'Sibuk',
            self::Libur => 'Libur',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Tersedia => 'success',
            self::Sibuk => 'warning',
            self::Libur => 'gray',
        };
    }
}
