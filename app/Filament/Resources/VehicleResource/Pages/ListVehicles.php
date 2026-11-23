<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Enums\VehicleType;
use App\Filament\Resources\VehicleResource;
use App\Models\Vehicle;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    /** Ringkasan di bawah judul. */
    public function getSubheading(): ?string
    {
        $counts = $this->typeCounts();

        return array_sum($counts).' kendaraan · '.($counts[VehicleType::Motor->value] ?? 0).' motor, '
            .($counts[VehicleType::Mobil->value] ?? 0).' mobil';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Kendaraan')->icon('heroicon-m-plus'),
        ];
    }

    /** Tab jenis kendaraan: Semua, Motor, Mobil. */
    public function getTabs(): array
    {
        $counts = $this->typeCounts();

        $tabs = ['semua' => Tab::make('Semua')->badge(array_sum($counts))];

        foreach (VehicleType::cases() as $type) {
            $tabs[$type->value] = Tab::make($type->getLabel())
                ->badge($counts[$type->value] ?? 0)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', $type->value));
        }

        return $tabs;
    }

    /** @return array<string, int> */
    protected function typeCounts(): array
    {
        return Vehicle::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($n): int => (int) $n)
            ->all();
    }
}
