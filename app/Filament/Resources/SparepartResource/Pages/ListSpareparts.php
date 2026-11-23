<?php

namespace App\Filament\Resources\SparepartResource\Pages;

use App\Filament\Resources\SparepartResource;
use App\Models\Sparepart;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSpareparts extends ListRecords
{
    protected static string $resource = SparepartResource::class;

    /** Ringkasan gudang di bawah judul. */
    public function getSubheading(): ?string
    {
        $types = Sparepart::query()->count();
        $units = (int) Sparepart::query()->sum('stock');
        $low = Sparepart::query()->lowStock()->count();

        return $types.' jenis · '.number_format($units, 0, ',', '.').' unit di gudang'
            .($low > 0 ? ' · '.$low.' perlu dibeli' : ' · semua stok aman');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Sparepart')->icon('heroicon-m-plus'),
        ];
    }

    /** Tab kondisi stok: Semua, Perlu dibeli (menipis + habis), Habis, Aman. */
    public function getTabs(): array
    {
        $all = Sparepart::query()->count();
        $low = Sparepart::query()->lowStock()->count();
        $out = Sparepart::query()->where('stock', '<=', 0)->count();

        return [
            'semua' => Tab::make('Semua')->badge($all),
            'perlu' => Tab::make('Perlu dibeli')
                ->badge($low)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->lowStock()),
            'habis' => Tab::make('Habis')
                ->badge($out)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock', '<=', 0)),
            'aman' => Tab::make('Aman')
                ->badge($all - $low)
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('stock', '>', 'min_stock')),
        ];
    }
}
