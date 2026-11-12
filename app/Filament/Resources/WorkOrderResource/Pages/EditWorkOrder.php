<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Support\ServiceAction;
use App\Services\WorkOrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Hanya bisa dibuka saat status Baru & belum ada sparepart (WorkOrderPolicy::update). */
class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    public function getTitle(): string
    {
        return 'Ubah '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return app(WorkOrderService::class)->update($record, $data, auth()->user());
        } catch (BusinessRuleException $e) {
            ServiceAction::haltWith($e->getMessage());
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan Work Order disimpan';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
