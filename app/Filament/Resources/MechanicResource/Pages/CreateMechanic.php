<?php

namespace App\Filament\Resources\MechanicResource\Pages;

use App\Enums\MechanicStatus;
use App\Filament\Resources\MechanicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMechanic extends CreateRecord
{
    protected static string $resource = MechanicResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Mekanik baru selalu mulai sebagai Tersedia
        $data['status'] = MechanicStatus::Tersedia->value;

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Mekanik berhasil disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
