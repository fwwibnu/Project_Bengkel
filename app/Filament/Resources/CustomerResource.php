<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\VehiclesRelationManager;
use App\Models\Customer;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $slug = 'pelanggan';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    protected static ?string $recordTitleAttribute = 'name';

    /** Field pelanggan, dipakai juga di form "Pelanggan baru" pada Buat Work Order. */
    public static function customerFields(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('phone')
                ->label('No. HP')
                ->tel()
                ->required()
                ->maxLength(20),
            Forms\Components\Textarea::make('address')
                ->label('Alamat')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data pelanggan')
                ->columns(2)
                ->schema(static::customerFields()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('vehicles')
                ->withMax('workOrders', 'created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Customer $record): ?string => $record->address),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicles.plate_number')
                    ->label('Kendaraan')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->placeholder('Belum ada kendaraan'),

                Tables\Columns\TextColumn::make('work_orders_max_created_at')
                    ->label('Servis terakhir')
                    ->formatStateUsing(fn ($state): string => Format::tanggal($state))
                    ->placeholder('Belum pernah')
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()->label('Lihat / Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalDescription('Kendaraan milik pelanggan ini ikut terhapus.')
                    ->before(function (Tables\Actions\DeleteAction $action, Customer $record): void {
                        if ($record->isUsed()) {
                            Notification::make()
                                ->danger()
                                ->title('Pelanggan tidak bisa dihapus')
                                ->body('Pelanggan ini sudah punya riwayat Work Order. Cukup ubah datanya.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('Pelanggan tidak ditemukan')
            ->emptyStateDescription('Coba kata kunci lain, atau tambahkan sebagai pelanggan baru.');
    }

    public static function getRelations(): array
    {
        return [
            VehiclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
