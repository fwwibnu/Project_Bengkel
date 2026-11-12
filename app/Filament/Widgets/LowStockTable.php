<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SparepartResource;
use App\Models\Sparepart;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Gate;

class LowStockTable extends TableWidget
{
    protected static ?string $heading = 'Stok menipis';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Gate::allows('lihat-dashboard');
    }

    public function table(Table $table): Table
    {
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return $table
            ->heading('Stok menipis')
            ->description('Sparepart yang stoknya habis atau di bawah batas minimal. Segera lakukan pengadaan.')
            ->query(Sparepart::query()->lowStock())
            ->defaultSort('stock')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->color('gray'),
                Tables\Columns\TextColumn::make('name')->label('Nama sparepart')->weight(FontWeight::SemiBold),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color(fn (Sparepart $record): string => $record->isOutOfStock() ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('min_stock')->label('Minimal')->alignEnd(),
                Tables\Columns\TextColumn::make('kondisi')
                    ->label('Keterangan')
                    ->getStateUsing(fn (Sparepart $record): string => $record->stockLabel() ?? 'Aman')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Habis' ? 'danger' : 'warning'),
            ])
            ->headerActions($isAdmin ? [
                Tables\Actions\Action::make('semua')
                    ->label('Lihat semua sparepart')
                    ->link()
                    ->url(SparepartResource::getUrl('index')),
            ] : [])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Semua stok aman')
            ->emptyStateDescription('Tidak ada sparepart yang menipis atau habis.');
    }
}
