<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\VehiclesRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
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
                ->withMax('workOrders', 'created_at')
                ->withExists(['workOrders as in_shop' => fn (Builder $q) => $q->active()]))
            // Tiap pelanggan tampil sebagai kartu (view: filament/tables/cards/customer)
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ViewColumn::make('kartu')
                        ->label('Pelanggan')
                        ->view('filament.tables.cards.customer')
                        ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $q) => $q
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('vehicles', fn (Builder $v) => $v->where('plate_number', 'like', "%{$search}%")))),
                ]),
            ])
            ->searchPlaceholder('Cari nama, HP, atau plat')
            ->defaultSort('name')
            ->paginated([12, 24, 48])
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
