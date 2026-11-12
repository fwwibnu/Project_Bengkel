<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Status Work Order. Urutan wajib: Baru → Sedang Dikerjakan → Selesai → Dibayar.
 * Perpindahan status hanya lewat App\Services\WorkOrderService dan PaymentService.
 */
enum WorkOrderStatus: string implements HasLabel, HasColor, HasIcon
{
    case Baru = 'baru';
    case Dikerjakan = 'dikerjakan';
    case Selesai = 'selesai';
    case Dibayar = 'dibayar';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dikerjakan => 'Sedang Dikerjakan',
            self::Selesai => 'Selesai',
            self::Dibayar => 'Dibayar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baru => 'info',
            self::Dikerjakan => 'warning',
            self::Selesai => 'success',
            self::Dibayar => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Baru => 'heroicon-m-sparkles',
            self::Dikerjakan => 'heroicon-m-wrench',
            self::Selesai => 'heroicon-m-check-circle',
            self::Dibayar => 'heroicon-m-banknotes',
        };
    }

    /** Status yang berarti WO masih dipegang mekanik. */
    public static function aktif(): array
    {
        return [self::Baru, self::Dikerjakan];
    }

    /** Status berikutnya yang sah (null = tidak ada). */
    public function berikutnya(): ?self
    {
        return match ($this) {
            self::Baru => self::Dikerjakan,
            self::Dikerjakan => self::Selesai,
            self::Selesai => self::Dibayar,
            self::Dibayar => null,
        };
    }
}
