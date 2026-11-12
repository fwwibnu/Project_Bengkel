<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Filament\Resources\SparepartResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Kolom stok tidak ada di form edit: stok hanya berubah lewat "Tambah Stok"
 * dan pencatatan sparepart oleh mekanik (StockService).
 */
class EditSparepart extends EditRecord
{
    protected static string $resource = SparepartResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan sparepart disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
