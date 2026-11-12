<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'akun-pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Akun Pengguna';

    protected static ?string $modelLabel = 'akun';

    protected static ?string $pluralModelLabel = 'Akun Pengguna';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data akun')
                ->description('Hanya Admin yang bisa membuat akun. Tidak ada pendaftaran mandiri.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama lengkap')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required()
                        ->alphaDash()
                        ->minLength(3)
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('Dipakai untuk login. Huruf, angka, strip, atau garis bawah.'),

                    Forms\Components\TextInput::make('email')
                        ->label('Email (opsional)')
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('role')
                        ->label('Peran')
                        ->options(Role::class)
                        ->required()
                        ->native(false)
                        ->disabled(fn (?User $record): bool => $record !== null && $record->is(auth()->user()))
                        ->helperText(fn (?User $record): ?string => $record !== null && $record->is(auth()->user())
                            ? 'Kamu tidak bisa mengubah peranmu sendiri.'
                            : null),

                    Forms\Components\TextInput::make('password')
                        ->label(fn (string $operation): string => $operation === 'create' ? 'Password' : 'Password baru')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(255)
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Minimal 8 karakter. Disimpan dalam bentuk terenkripsi (hash).'
                            : 'Kosongkan kalau tidak ingin mengganti password (dipakai untuk reset password).'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (User $record): ?string => $record->is(auth()->user()) ? '(akun kamu)' : $record->email),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->formatStateUsing(fn ($state): string => Format::tanggal($state))
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran')
                    ->options(Role::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading(fn (User $record): string => 'Hapus akun '.$record->name.'?')
                    ->modalDescription('Akun tidak bisa dipakai login lagi. Riwayat Work Order dan transaksi yang pernah dikerjakannya tetap tersimpan.')
                    ->modalSubmitActionLabel('Ya, hapus'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
