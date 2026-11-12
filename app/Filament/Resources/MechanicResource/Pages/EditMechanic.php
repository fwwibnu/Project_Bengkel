<?php

namespace App\Filament\Resources\MechanicResource\Pages;

use App\Filament\Resources\MechanicResource;
use Filament\Resources\Pages\EditRecord;

class EditMechanic extends EditRecord
{
    protected static string $resource = MechanicResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Status tidak bisa diubah dari form (otomatis / lewat tombol Set Libur)
        unset($data['status']);

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan mekanik disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
