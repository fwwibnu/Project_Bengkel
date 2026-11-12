<?php

namespace App\Filament\Resources;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $slug = 'work-orders';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Work Order';

    protected static ?string $modelLabel = 'Work Order';

    protected static ?string $pluralModelLabel = 'Work Order';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $active = WorkOrder::query()->active()->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Work Order aktif';
    }

    /* ---------------------------------------------------------------
     | Form buat / ubah (Admin)
     |---------------------------------------------------------------*/

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('1. Pelanggan & kendaraan')
                ->description('Cari dulu. Kalau belum terdaftar, tambahkan langsung dari sini.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Pelanggan')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Customer $record): string => "{$record->name} · {$record->phone}")
                        ->searchable(['name', 'phone'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('vehicle_id', null))
                        ->createOptionForm(CustomerResource::customerFields())
                        ->createOptionModalHeading('Pelanggan baru'),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Kendaraan')
                        ->relationship(
                            'vehicle',
                            'plate_number',
                            fn (Builder $query, Get $get) => $query->where('customer_id', $get('customer_id')),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Vehicle $record): string => $record->getLabel())
                        ->searchable(['plate_number', 'brand', 'model'])
                        ->preload()
                        ->required()
                        ->disabled(fn (Get $get): bool => blank($get('customer_id')))
                        ->helperText('Hanya kendaraan milik pelanggan yang dipilih.')
                        ->createOptionForm(VehicleResource::vehicleFields())
                        ->createOptionModalHeading('Kendaraan baru')
                        ->createOptionUsing(function (array $data, Get $get): int {
                            $vehicle = new Vehicle($data);
                            $vehicle->customer_id = (int) $get('customer_id');
                            $vehicle->save();

                            return $vehicle->getKey();
                        }),
                ]),

            Forms\Components\Section::make('2. Keluhan & kebutuhan servis')
                ->schema([
                    Forms\Components\Textarea::make('complaint')
                        ->label('Keluhan')
                        ->placeholder('Mis. Rem depan bunyi saat mengerem pelan')
                        ->required()
                        ->rows(3)
                        ->maxLength(2000)
                        ->validationMessages(['required' => 'Keluhan wajib diisi.']),
                ]),

            Forms\Components\Section::make('3. Tugaskan mekanik')
                ->description('Hanya mekanik berstatus Tersedia yang bisa dipilih.')
                ->schema([
                    Forms\Components\Radio::make('mechanic_id')
                        ->hiddenLabel()
                        ->options(fn (?WorkOrder $record): array => static::mechanicOptions($record))
                        ->descriptions(fn (?WorkOrder $record): array => static::mechanicDescriptions($record))
                        ->required()
                        ->columns(['sm' => 2, 'xl' => 4])
                        ->validationMessages(['required' => 'Pilih mekanik yang akan mengerjakan.']),

                    Forms\Components\Placeholder::make('mekanik_kosong')
                        ->hiddenLabel()
                        ->content('Semua mekanik sedang sibuk atau libur. Tunggu sampai ada Work Order yang selesai, atau aktifkan mekanik yang libur di menu Mekanik.')
                        ->visible(fn (?WorkOrder $record): bool => static::mechanicOptions($record) === []),
                ]),
        ]);
    }

    /** Mekanik Tersedia (+ mekanik WO ini sendiri saat edit). */
    public static function mechanicOptions(?WorkOrder $record): array
    {
        return static::mechanicQuery($record)->pluck('name', 'id')->all();
    }

    public static function mechanicDescriptions(?WorkOrder $record): array
    {
        return static::mechanicQuery($record)
            ->get(['id', 'specialty', 'status'])
            ->mapWithKeys(fn (Mechanic $m) => [$m->id => trim(($m->specialty ?: 'Mekanik umum').' · '.$m->status->getLabel())])
            ->all();
    }

    protected static function mechanicQuery(?WorkOrder $record): Builder
    {
        return Mechanic::query()
            ->where(function (Builder $query) use ($record) {
                $query->where('status', MechanicStatus::Tersedia->value);

                if ($record?->mechanic_id) {
                    $query->orWhere('id', $record->mechanic_id);
                }
            })
            ->orderBy('name');
    }

    /* ---------------------------------------------------------------
     | Daftar
     |---------------------------------------------------------------*/

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'vehicle', 'mechanic']))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. WO')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (WorkOrder $record): string => Format::tanggalJam($record->created_at)),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Kendaraan')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (WorkOrder $record): string => trim(($record->vehicle?->brand ?? '').' '.($record->vehicle?->model ?? ''))),

                Tables\Columns\TextColumn::make('complaint')
                    ->label('Keluhan')
                    ->limit(40)
                    ->tooltip(fn (WorkOrder $record): string => $record->complaint)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state): string => Format::tanggalJam($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mechanic_id')
                    ->label('Mekanik')
                    ->relationship('mechanic', 'name'),
            ])
            ->recordUrl(fn (WorkOrder $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Belum ada Work Order')
            ->emptyStateDescription('Buat Work Order saat ada kendaraan masuk untuk diservis.');
    }

    /* ---------------------------------------------------------------
     | Detail
     |---------------------------------------------------------------*/

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Grid::make(['default' => 1, 'lg' => 3])->schema([
                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Informasi')
                        ->columns(['default' => 1, 'sm' => 2])
                        ->schema([
                            Infolists\Components\TextEntry::make('customer.name')
                                ->label('Pelanggan')
                                ->weight(FontWeight::SemiBold),
                            Infolists\Components\TextEntry::make('vehicle.plate_number')
                                ->label('Kendaraan')
                                ->weight(FontWeight::SemiBold)
                                ->formatStateUsing(fn ($state, WorkOrder $record): string => $record->vehicle?->getLabel() ?? $state)
                                ->url(fn (WorkOrder $record): ?string => $record->vehicle && auth()->user()?->can('view', $record->vehicle)
                                    ? VehicleResource::getUrl('view', ['record' => $record->vehicle])
                                    : null),
                            Infolists\Components\TextEntry::make('mechanic.name')
                                ->label('Mekanik')
                                ->weight(FontWeight::SemiBold),
                            Infolists\Components\TextEntry::make('creator.name')
                                ->label('Dibuat oleh')
                                ->placeholder('–'),
                            Infolists\Components\TextEntry::make('complaint')
                                ->label('Keluhan')
                                ->columnSpanFull(),
                            Infolists\Components\TextEntry::make('work_notes')
                                ->label('Pekerjaan yang dilakukan')
                                ->placeholder('Belum diisi mekanik')
                                ->columnSpanFull(),
                        ]),

                    Infolists\Components\Section::make('Sparepart terpakai')
                        ->schema([
                            Infolists\Components\ViewEntry::make('items')
                                ->hiddenLabel()
                                ->view('filament.infolists.wo-items'),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Status')
                        ->schema([
                            Infolists\Components\ViewEntry::make('status')
                                ->hiddenLabel()
                                ->view('filament.infolists.wo-timeline'),
                        ]),

                    Infolists\Components\Section::make('Pembayaran')
                        ->visible(fn (WorkOrder $record): bool => $record->payment !== null)
                        ->schema([
                            Infolists\Components\TextEntry::make('payment.number')->label('No. transaksi'),
                            Infolists\Components\TextEntry::make('payment.paid_at')
                                ->label('Dibayar')
                                ->formatStateUsing(fn ($state): string => Format::tanggalJam($state)),
                            Infolists\Components\TextEntry::make('payment.method')->label('Metode')->badge(),
                            Infolists\Components\TextEntry::make('payment.service_fee')
                                ->label('Biaya jasa')
                                ->formatStateUsing(fn ($state): string => Format::rupiah($state)),
                            Infolists\Components\TextEntry::make('payment.grand_total')
                                ->label('Total')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn ($state): string => Format::rupiah($state)),
                            Infolists\Components\TextEntry::make('payment.cashier.name')->label('Kasir'),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }

    /** Hitung jumlah WO per status untuk badge tab. */
    public static function statusCounts(): array
    {
        $counts = WorkOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $result = ['semua' => (int) array_sum($counts)];

        foreach (WorkOrderStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }
}
