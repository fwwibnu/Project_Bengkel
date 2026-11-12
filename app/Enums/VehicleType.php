<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VehicleType: string implements HasLabel
{
    case Motor = 'motor';
    case Mobil = 'mobil';

    public function getLabel(): string
    {
        return match ($this) {
            self::Motor => 'Motor',
            self::Mobil => 'Mobil',
        };
    }
}
