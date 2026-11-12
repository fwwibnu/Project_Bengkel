<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan pelanggan disimpan';
    }
}
