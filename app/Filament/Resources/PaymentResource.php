<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Support\Format;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Riwayat Transaksi (read-only) untuk Kasir & Admin. Pembayaran baru dibuat di halaman Pembayaran. */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $slug = 'riwayat-transaksi';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Kasir';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static ?string $modelLabel = 'transaksi';

    protected static ?string $pluralModelLabel = 'Riwayat Transaksi';

    protected static ?string $recordTitleAttribute = 'number';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['workOrder.vehicle', 'workOrder.customer', 'cashier']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Payment $record): string => Format::tanggalJam($record->paid_at)),
                Tables\Columns\TextColumn::make('workOrder.number')
                    ->label('No. WO')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('workOrder.vehicle.plate_number')
                    ->label('Kendaraan')
                    ->searchable()
                    ->weight(FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('workOrder.customer.name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Metode')
                    ->badge(),
                Tables\Columns\TextColumn::make('service_fee')
                    ->label('Jasa')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sparepart_total')
                    ->label('Sparepart')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->sortable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'tabular-nums']),
                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->toggleable(),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->label('Metode')
                    ->options(PaymentMethod::class),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari tanggal')->native(false)->displayFormat('d M Y'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai tanggal')->native(false)->displayFormat('d M Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['dari'] ?? null, fn (Builder $q, $date) => $q->whereDate('paid_at', '>=', $date))
                        ->when($data['sampai'] ?? null, fn (Builder $q, $date) => $q->whereDate('paid_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\Action::make('nota')
                    ->label('Nota')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->url(fn (Payment $record): string => route('nota.show', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-receipt-percent')
            ->emptyStateHeading('Belum ada transaksi')
            ->emptyStateDescription('Transaksi muncul setelah Kasir memproses pembayaran.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
