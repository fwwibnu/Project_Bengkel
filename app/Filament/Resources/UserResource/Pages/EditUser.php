<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus akun')
                ->modalDescription('Akun tidak bisa dipakai login lagi. Riwayat yang pernah dikerjakannya tetap tersimpan.'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pengaman di server: admin tidak bisa mengubah perannya sendiri
        if ($this->getRecord()->is(auth()->user())) {
            unset($data['role']);
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan akun disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
