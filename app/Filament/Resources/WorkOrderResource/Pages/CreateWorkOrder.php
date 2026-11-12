<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Support\ServiceAction;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Buat Work Order';
    }

    /** Lewat WorkOrderService: nomor otomatis, cek mekanik Tersedia, mekanik → Sibuk. */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(WorkOrderService::class)->create($data, auth()->user());
        } catch (BusinessRuleException $e) {
            ServiceAction::haltWith($e->getMessage());
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        /** @var WorkOrder $record */
        $record = $this->getRecord();

        return Notification::make()
            ->success()
            ->title('Work Order '.$record->number.' berhasil disimpan')
            ->body($record->mechanic?->name.' sekarang berstatus Sibuk.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
