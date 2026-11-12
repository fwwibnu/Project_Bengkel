<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Empat peran tetap di sistem bengkel.
 * Nilai (value) disimpan di kolom users.role.
 */
enum Role: string implements HasLabel, HasColor
{
    case Admin = 'admin';
    case Mekanik = 'mekanik';
    case Kasir = 'kasir';
    case Pemilik = 'pemilik';

    // Teks yang tampil di tabel, form, dan badge
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Mekanik => 'Mekanik',
            self::Kasir => 'Kasir',
            self::Pemilik => 'Pemilik Bengkel',
        };
    }

    // Warna badge peran (nama warna didaftarkan di AppPanelProvider)
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Admin => 'primary',   // oranye
            self::Mekanik => 'info',    // biru
            self::Kasir => 'teal',      // hijau toska
            self::Pemilik => 'purple',  // ungu
        };
    }
}