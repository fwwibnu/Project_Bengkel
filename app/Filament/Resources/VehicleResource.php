<?php

namespace App\Filament\Resources;

use App\Enums\VehicleType;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers\ServiceHistoryRelationManager;
use App\Models\Vehicle;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $slug = 'kendaraan';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Kendaraan';

    protected static ?string $modelLabel = 'kendaraan';

    protected static ?string $pluralModelLabel = 'Kendaraan';

    protected static ?string $recordTitleAttribute = 'plate_number';

    /** Field kendaraan (tanpa pemilik). Dipakai di sini, di Pelanggan, dan di Buat Work Order. */
    public static function vehicleFields(): array
    {
        return [
            Forms\Components\TextInput::make('plate_number')
                ->label('Nomor polisi')
                ->placeholder('B 1234 KZT')
                ->required()
                ->maxLength(15)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('plate_number', Vehicle::normalizePlate($state)))
                ->unique(table: Vehicle::class, column: 'plate_number', ignoreRecord: true)
                ->validationMessages(['unique' => 'Nomor polisi ini sudah terdaftar.']),
            Forms\Components\TextInput::make('brand')
                ->label('Merek')
                ->placeholder('Honda')
                ->required()
                ->maxLength(50),
            Forms\Components\TextInput::make('model')
                ->label('Model')
                ->placeholder('Vario 125')
                ->maxLength(50),
            Forms\Components\Select::make('type')
                ->label('Jenis')
                ->options(VehicleType::class)
                ->default(VehicleType::Motor->value)
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('year')
                ->label('Tahun (opsional)')
                ->numeric()
                ->integer()
                ->minValue(1970)
                ->maxValue((int) date('Y') + 1),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data kendaraan')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Pemilik')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} · {$record->phone}")
                        ->searchable(['name', 'phone'])
                        ->preload()
                        ->required()
                        ->createOptionForm(CustomerResource::customerFields())
                        ->createOptionModalHeading('Pelanggan baru')
                        ->columnSpanFull(),
                    ...static::vehicleFields(),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Data kendaraan')
                ->columns(['default' => 2, 'lg' => 5])
                ->schema([
                    Infolists\Components\TextEntry::make('plate_number')
                        ->label('Nomor polisi')
                        ->weight(FontWeight::Bold),
                    Infolists\Components\TextEntry::make('brand')
                        ->label('Merek / model')
                        ->formatStateUsing(fn ($state, Vehicle $record): string => trim($record->brand.' '.$record->model)),
                    Infolists\Components\TextEntry::make('customer.name')
                        ->label('Pemilik')
                        ->url(fn (Vehicle $record): ?string => auth()->user()?->can('update', $record->customer)
                            ? CustomerResource::getUrl('edit', ['record' => $record->customer])
                            : null)
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('customer.phone')
                        ->label('No. HP pemilik'),
                    Infolists\Components\TextEntry::make('work_orders_count')
                        ->label('Total servis')
                        ->getStateUsing(fn (Vehicle $record): string => $record->workOrders()->count().' kali'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('customer')
                ->withCount('workOrders')
                ->withMax('workOrders', 'created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Nomor polisi')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Vehicle $record): string => trim($record->brand.' '.$record->model)),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->description(fn (Vehicle $record): ?string => $record->customer?->phone),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('work_orders_count')
                    ->label('Servis')
                    ->suffix('x')
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_orders_max_created_at')
                    ->label('Servis terakhir')
                    ->formatStateUsing(fn ($state): string => Format::tanggal($state))
                    ->placeholder('Belum pernah')
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('plate_number')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(VehicleType::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Riwayat'),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(function (Tables\Actions\DeleteAction $action, Vehicle $record): void {
                        if ($record->isUsed()) {
                            Notification::make()
                                ->danger()
                                ->title('Kendaraan tidak bisa dihapus')
                                ->body('Kendaraan ini sudah punya riwayat servis. Cukup ubah datanya.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('Kendaraan tidak ditemukan')
            ->emptyStateDescription('Coba kata kunci lain, atau tambahkan kendaraan dari halaman pelanggan.');
    }

    public static function getRelations(): array
    {
        return [
            ServiceHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
