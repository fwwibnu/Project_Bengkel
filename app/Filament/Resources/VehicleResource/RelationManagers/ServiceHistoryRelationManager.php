<?php

namespace App\Filament\Resources\VehicleResource\RelationManagers;

use App\Filament\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Support\Format;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat servis kendaraan = daftar Work Order kendaraan ini (tanpa tabel terpisah).
 * Hanya baca.
 */
class ServiceHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrders';

    protected static ?string $title = 'Riwayat servis';

    protected static ?string $modelLabel = 'Work Order';

    public function isReadOnly(): bool
    {
        return true;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true; // akses halaman induk sudah dicek oleh VehiclePolicy
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Diambil otomatis dari Work Order kendaraan ini.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['mechanic', 'payment', 'items.sparepart']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->formatStateUsing(fn ($state): string => Format::tanggal($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('No. WO')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('complaint')
                    ->label('Keluhan')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('work_notes')
                    ->label('Pekerjaan & sparepart')
                    ->placeholder('Belum dikerjakan')
                    ->limit(50)
                    ->wrap()
                    ->description(fn (WorkOrder $record): ?string => $record->items->isEmpty()
                        ? null
                        : $record->items->map(fn ($item) => $item->sparepart?->name.' ×'.$item->qty)->join(', ')),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (WorkOrder $record): ?int => $record->payment?->grand_total)
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->placeholder('–')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (WorkOrder $record): ?string => auth()->user()?->can('view', $record)
                ? WorkOrderResource::getUrl('view', ['record' => $record])
                : null)
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('Belum ada riwayat servis')
            ->emptyStateDescription('Riwayat muncul setelah kendaraan ini dibuatkan Work Order.');
    }
}
