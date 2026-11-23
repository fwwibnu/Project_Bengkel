<?php

namespace App\Filament\Resources;

use App\Enums\MechanicStatus;
use App\Enums\Role;
use App\Filament\Resources\MechanicResource\Pages;
use App\Filament\Support\ServiceAction;
use App\Models\Mechanic;
use App\Services\MechanicService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MechanicResource extends Resource
{
    protected static ?string $model = Mechanic::class;

    protected static ?string $slug = 'mekanik';

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Mekanik';

    protected static ?string $modelLabel = 'mekanik';

    protected static ?string $pluralModelLabel = 'Mekanik';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data mekanik')
                ->description('Status Sibuk dan Tersedia berubah otomatis mengikuti Work Order. Status Libur diatur dari daftar mekanik.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('phone')
                        ->label('No. HP')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('specialty')
                        ->label('Keahlian')
                        ->placeholder('Mis. Mesin, servis rutin')
                        ->maxLength(100),

                    Forms\Components\Select::make('user_id')
                        ->label('Akun login (peran Mekanik)')
                        ->relationship(
                            'user',
                            'name',
                            fn (Builder $query) => $query
                                ->where('role', Role::Mekanik->value)
                                ->whereNull('users.deleted_at'),
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->name} ({$record->username})")
                        ->searchable(['name', 'username'])
                        ->preload()
                        ->unique(ignoreRecord: true)
                        ->helperText('Akun dibuat di menu Akun Pengguna dengan peran Mekanik. Tanpa akun, mekanik tidak bisa membuka "Work Order Saya".'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['user', 'activeWorkOrders.vehicle', 'activeWorkOrders.customer'])
                ->withCount(['workOrders as finished_month' => fn (Builder $q) => $q
                    ->whereNotNull('finished_at')
                    ->whereBetween('finished_at', [now()->startOfMonth(), now()->endOfMonth()])]))
            // Tiap mekanik tampil sebagai kartu (view: filament/tables/cards/mechanic)
            ->contentGrid(['md' => 2])
            ->columns([
                Stack::make([
                    Tables\Columns\ViewColumn::make('kartu')
                        ->label('Mekanik')
                        ->view('filament.tables.cards.mechanic')
                        ->searchable(['name', 'specialty']),
                ]),
            ])
            ->searchPlaceholder('Cari nama atau keahlian')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\Action::make('liburkan')
                    ->label('Set Libur')
                    ->icon('heroicon-m-moon')
                    ->color('gray')
                    ->visible(fn (Mechanic $record): bool => $record->status !== MechanicStatus::Libur && (auth()->user()?->can('update', $record) ?? false))
                    // Masih memegang WO aktif → tombol nonaktif (aturan yang sama dicek ulang di MechanicService)
                    ->disabled(fn (Mechanic $record): bool => $record->activeWorkOrders->isNotEmpty())
                    ->tooltip(fn (Mechanic $record): ?string => $record->activeWorkOrders->isNotEmpty() ? 'Masih memegang Work Order aktif' : null)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Mechanic $record): string => 'Set '.$record->name.' libur?')
                    ->modalDescription('Mekanik yang libur tidak bisa dipilih saat membuat Work Order.')
                    ->modalSubmitActionLabel('Ya, set libur')
                    ->action(fn (Mechanic $record) => ServiceAction::run(
                        fn () => app(MechanicService::class)->setLibur($record, auth()->user()),
                        'Status mekanik diubah ke Libur',
                    )),
                Tables\Actions\Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-m-sun')
                    ->color('success')
                    ->visible(fn (Mechanic $record): bool => $record->status === MechanicStatus::Libur && (auth()->user()?->can('update', $record) ?? false))
                    ->action(fn (Mechanic $record) => ServiceAction::run(
                        fn () => app(MechanicService::class)->setAktif($record, auth()->user()),
                        'Mekanik aktif kembali',
                    )),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(function (Tables\Actions\DeleteAction $action, Mechanic $record): void {
                        if ($record->isUsed()) {
                            Notification::make()
                                ->danger()
                                ->title('Mekanik tidak bisa dihapus')
                                ->body('Mekanik ini sudah punya riwayat Work Order. Cukup ubah datanya atau set Libur.')
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-wrench')
            ->emptyStateHeading('Belum ada mekanik')
            ->emptyStateDescription('Tambahkan mekanik supaya Work Order bisa ditugaskan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMechanics::route('/'),
            'create' => Pages\CreateMechanic::route('/create'),
            'edit' => Pages\EditMechanic::route('/{record}/edit'),
        ];
    }
}
