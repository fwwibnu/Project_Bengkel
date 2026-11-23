<?php

namespace App\Filament\Resources\MechanicResource\Pages;

use App\Enums\MechanicStatus;
use App\Filament\Resources\MechanicResource;
use App\Models\Mechanic;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListMechanics extends ListRecords
{
    protected static string $resource = MechanicResource::class;

    public function getSubheading(): ?string
    {
        return 'Status Tersedia/Sibuk berubah otomatis mengikuti Work Order. Status Libur diatur di sini.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Mekanik')->icon('heroicon-m-plus'),
        ];
    }

    /** Tab status mekanik: Semua, Tersedia, Sibuk, Libur. */
    public function getTabs(): array
    {
        $counts = Mechanic::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $tabs = ['semua' => Tab::make('Semua')->badge((int) $counts->sum())];

        foreach (MechanicStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge((int) ($counts[$status->value] ?? 0))
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
