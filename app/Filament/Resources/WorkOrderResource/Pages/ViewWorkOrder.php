<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Filament\Support\ServiceAction;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewWorkOrder extends ViewRecord
{
    protected static string $resource = WorkOrderResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->number;
    }

    public function getSubheading(): ?string
    {
        /** @var WorkOrder $record */
        $record = $this->getRecord();

        return 'Status: '.$record->status->getLabel().' · dibuat '.\App\Support\Format::tanggalJam($record->created_at);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('nota')
                ->label('Cetak Nota')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->visible(fn (WorkOrder $record): bool => $record->payment !== null)
                ->url(fn (WorkOrder $record): string => route('nota.show', $record->payment))
                ->openUrlInNewTab(),

            Actions\EditAction::make()->label('Ubah'),

            Actions\Action::make('hapus')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->visible(fn (WorkOrder $record): bool => auth()->user()?->can('delete', $record) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Hapus Work Order ini?')
                ->modalDescription('Mekanik yang ditugaskan akan kembali Tersedia. Data yang dihapus tidak bisa dikembalikan.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->action(function (WorkOrder $record): void {
                    $ok = ServiceAction::run(
                        fn () => app(WorkOrderService::class)->delete($record, auth()->user()),
                        'Work Order dihapus',
                    );

                    if ($ok) {
                        $this->redirect(WorkOrderResource::getUrl('index'));
                    }
                }),
        ];
    }
}
