<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparepartResource\Pages;
use App\Filament\Support\ServiceAction;
use App\Models\Sparepart;
use App\Services\StockService;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SparepartResource extends Resource
{
    protected static ?string $model = Sparepart::class;

    protected static ?string $slug = 'sparepart';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sparepart';

    protected static ?string $modelLabel = 'sparepart';

    protected static ?string $pluralModelLabel = 'Sparepart';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $low = Sparepart::query()->lowStock()->count();

        return $low > 0 ? (string) $low : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Stok menipis';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data sparepart')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Kode')
                        ->placeholder('Kosongkan untuk dibuat otomatis (SP-001)')
                        ->maxLength(20)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->required(fn (string $operation): bool => $operation === 'edit')
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama sparepart')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('stock')
                        ->label('Stok awal')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1_000_000)
                        ->default(0)
                        ->required()
                        ->visibleOn('create')
                        ->helperText('Setelah disimpan, stok hanya bisa ditambah lewat tombol "Tambah Stok".'),

                    Forms\Components\TextInput::make('min_stock')
                        ->label('Stok minimal')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1_000_000)
                        ->default(5)
                        ->required()
                        ->helperText('Stok sama dengan atau di bawah angka ini ditandai "Menipis".'),

                    Forms\Components\TextInput::make('purchase_price')
                        ->label('Harga beli')
                        ->prefix('Rp')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1_000_000_000)
                        ->required(),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Harga jual')
                        ->prefix('Rp')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1_000_000_000)
                        ->required()
                        ->helperText('Harga ini yang dikunci saat mekanik mencatat sparepart.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->getStateUsing(fn (Sparepart $record): string => $record->stockLabel() ?? 'Aman')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Habis' => 'danger',
                        'Menipis' => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->alignEnd()
                    ->weight(FontWeight::Bold)
                    ->color(fn (Sparepart $record): ?string => $record->isOutOfStock() ? 'danger' : ($record->isLowStock() ? 'warning' : null))
                    ->extraAttributes(['class' => 'tabular-nums']),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Minimal')
                    ->sortable()
                    ->alignEnd()
                    ->extraAttributes(['class' => 'tabular-nums']),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga beli')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->sortable()
                    ->alignEnd()
                    ->toggleable()
                    ->extraAttributes(['class' => 'tabular-nums']),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga jual')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->sortable()
                    ->alignEnd()
                    ->extraAttributes(['class' => 'tabular-nums']),
            ])
            ->defaultSort('code')
            ->filters([
                Tables\Filters\Filter::make('menipis')
                    ->label('Hanya stok menipis')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->lowStock()),
            ])
            ->actions([
                static::tambahStokAction(),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading(fn (Sparepart $record): string => 'Hapus '.$record->name.'?')
                    ->before(function (Tables\Actions\DeleteAction $action, Sparepart $record): void {
                        if ($record->isUsed() || $record->movements()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Sparepart tidak bisa dihapus')
                                ->body('Sparepart ini sudah punya riwayat stok atau pemakaian di Work Order. Cukup ubah datanya.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateHeading('Belum ada sparepart')
            ->emptyStateDescription('Tambahkan sparepart supaya mekanik bisa mencatat pemakaiannya.');
    }

    public static function tambahStokAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('tambahStok')
            ->label('Tambah Stok')
            ->icon('heroicon-m-plus-circle')
            ->color('primary')
            ->visible(fn (Sparepart $record): bool => auth()->user()?->can('addStock', $record) ?? false)
            ->modalHeading('Tambah stok')
            ->modalDescription(fn (Sparepart $record): string => "{$record->name} · {$record->code} · stok sekarang {$record->stock}")
            ->modalSubmitActionLabel('Simpan')
            ->modalWidth('md')
            ->form([
                Forms\Components\TextInput::make('qty')
                    ->label('Jumlah masuk')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100_000)
                    ->default(1)
                    ->required(),
                Forms\Components\TextInput::make('note')
                    ->label('Keterangan')
                    ->placeholder('Mis. Pembelian dari supplier')
                    ->maxLength(255),
            ])
            ->action(function (Sparepart $record, array $data, Tables\Actions\Action $action): void {
                ServiceAction::run(
                    fn () => app(StockService::class)->addStock($record, (int) $data['qty'], $data['note'] ?? null, auth()->user()),
                    'Stok berhasil ditambah',
                    "{$record->name} bertambah {$data['qty']}. Tercatat di laporan sirkulasi stok.",
                    $action,
                );
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareparts::route('/'),
            'create' => Pages\CreateSparepart::route('/create'),
            'edit' => Pages\EditSparepart::route('/{record}/edit'),
        ];
    }
}
