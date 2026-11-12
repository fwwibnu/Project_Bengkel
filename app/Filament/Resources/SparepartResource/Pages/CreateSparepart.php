<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\SparepartResource;
use App\Filament\Support\ServiceAction;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSparepart extends CreateRecord
{
    protected static string $resource = SparepartResource::class;

    protected static bool $canCreateAnother = false;

    /** Lewat StockService supaya stok awal tercatat sebagai stok masuk. */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(StockService::class)->createSparepart($data, auth()->user());
        } catch (BusinessRuleException $e) {
            ServiceAction::haltWith($e->getMessage());
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sparepart berhasil disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
