<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    public function getSubheading(): ?string
    {
        return 'Catat keluhan, buat Work Order, lalu tugaskan mekanik yang tersedia.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buat Work Order')->icon('heroicon-m-plus'),
        ];
    }

    /** Tab status + jumlahnya: Semua, Baru, Sedang Dikerjakan, Selesai, Dibayar. */
    public function getTabs(): array
    {
        $counts = WorkOrderResource::statusCounts();

        $tabs = [
            'semua' => Tab::make('Semua')->badge($counts['semua']),
        ];

        foreach (WorkOrderStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge($counts[$status->value])
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
