<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\VehicleResource;
use App\Models\Vehicle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class VehiclesRelationManager extends RelationManager
{
    protected static string $relationship = 'vehicles';

    protected static ?string $title = 'Kendaraan';

    protected static ?string $modelLabel = 'kendaraan';

    protected static ?string $pluralModelLabel = 'Kendaraan';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->columns(2)->schema(VehicleResource::vehicleFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('plate_number')
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Nomor polisi')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek / model')
                    ->formatStateUsing(fn ($state, Vehicle $record): string => trim($record->brand.' '.$record->model)),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->placeholder('–'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Kendaraan')->icon('heroicon-m-plus'),
            ])
            ->actions([
                Tables\Actions\Action::make('riwayat')
                    ->label('Riwayat servis')
                    ->icon('heroicon-m-clock')
                    ->url(fn (Vehicle $record): string => VehicleResource::getUrl('view', ['record' => $record])),
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
            ->emptyStateHeading('Belum ada kendaraan')
            ->emptyStateDescription('Tambahkan kendaraan pelanggan ini supaya bisa dibuatkan Work Order.');
    }
}
