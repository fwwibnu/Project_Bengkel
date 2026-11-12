<?php

namespace App\Filament\Pages;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Support\Format;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Riwayat servis (read-only) untuk Kasir & Admin: Work Order yang sudah Selesai / Dibayar.
 * Diambil dari tabel work_orders, tanpa tabel riwayat terpisah.
 */
class RiwayatServis extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Riwayat Servis';

    protected static ?string $title = 'Riwayat Servis';

    protected static ?string $slug = 'riwayat-servis';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.riwayat-servis';

    public static function canAccess(): bool
    {
        return Gate::allows('lihat-riwayat-servis');
    }

    public function getSubheading(): ?string
    {
        return 'Servis yang sudah selesai dan dibayar. Cari berdasarkan nomor polisi, pelanggan, atau nomor WO.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()
                    ->whereIn('status', [WorkOrderStatus::Selesai->value, WorkOrderStatus::Dibayar->value])
                    ->with(['vehicle', 'customer', 'mechanic', 'payment', 'items.sparepart'])
            )
            ->defaultSort('finished_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->formatStateUsing(fn ($state): string => Format::tanggalJam($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('No. WO')
                    ->searchable()
                    ->weight(FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Kendaraan')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (WorkOrder $record): string => trim(($record->vehicle?->brand ?? '').' '.($record->vehicle?->model ?? ''))),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_notes')
                    ->label('Pekerjaan & sparepart')
                    ->limit(45)
                    ->wrap()
                    ->placeholder('–')
                    ->description(fn (WorkOrder $record): ?string => $record->items->isEmpty()
                        ? 'Tanpa sparepart'
                        : $record->items->map(fn ($item) => $item->sparepart?->name.' ×'.$item->qty)->join(', ')),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (WorkOrder $record): ?int => $record->payment?->grand_total)
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->placeholder('Belum dibayar')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        WorkOrderStatus::Selesai->value => WorkOrderStatus::Selesai->getLabel(),
                        WorkOrderStatus::Dibayar->value => WorkOrderStatus::Dibayar->getLabel(),
                    ]),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari tanggal')->native(false)->displayFormat('d M Y'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai tanggal')->native(false)->displayFormat('d M Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('finished_at', '>=', $date))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('finished_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\Action::make('nota')
                    ->label('Nota')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->visible(fn (WorkOrder $record): bool => $record->payment !== null)
                    ->url(fn (WorkOrder $record): ?string => $record->payment ? route('nota.show', $record->payment) : null)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('Belum ada riwayat servis')
            ->emptyStateDescription('Riwayat muncul setelah mekanik menandai Work Order selesai.');
    }
}
