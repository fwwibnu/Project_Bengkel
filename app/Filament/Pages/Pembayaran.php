<?php
 
namespace App\Filament\Pages;
 
use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Filament\Support\ServiceAction;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\PaymentService;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
 
/**
 * Kasir: pilih Work Order berstatus Selesai → isi biaya jasa & metode → bayar → nota.
 * Total dihitung ulang di server (PaymentService), browser hanya mengirim biaya jasa & metode.
 * Tampilan: tiap Work Order sebagai kartu (gaya di theme.css, .bk-bayar).
 */
class Pembayaran extends Page implements HasTable
{
    use InteractsWithTable;
 
    /** Pilihan cepat biaya jasa di modal bayar. */
    public const FEE_PRESETS = [50_000, 75_000, 100_000, 150_000];
 
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
 
    protected static ?string $navigationGroup = 'Kasir';
 
    protected static ?string $navigationLabel = 'Pembayaran';
 
    protected static ?string $title = 'Pembayaran';
 
    protected static ?string $slug = 'pembayaran';
 
    protected static ?int $navigationSort = 1;
 
    protected static string $view = 'filament.pages.pembayaran';
 
    public static function canAccess(): bool
    {
        return Gate::allows('proses-pembayaran');
    }
 
    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }
 
        $count = WorkOrder::query()->where('status', WorkOrderStatus::Selesai->value)->count();
 
        return $count > 0 ? (string) $count : null;
    }
 
    public function getSubheading(): ?string
    {
        return 'Servis yang sudah selesai dan menunggu dibayar.';
    }
 
    /** Ringkasan di atas daftar: jumlah menunggu & uang masuk hari ini. */
    protected function getViewData(): array
    {
        $today = Payment::query()->whereDate('paid_at', today());
 
        return [
            'waiting' => WorkOrder::query()->where('status', WorkOrderStatus::Selesai->value)->count(),
            'todayTotal' => (int) (clone $today)->sum('grand_total'),
            'todayCount' => (clone $today)->count(),
        ];
    }
 
    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()
                    ->where('status', WorkOrderStatus::Selesai->value)
                    ->with(['vehicle', 'customer', 'mechanic', 'items.sparepart'])
                    ->withSum('items as parts_total', 'subtotal')
            )
            ->defaultSort('finished_at', 'asc')
            ->searchPlaceholder('Cari plat, WO, pelanggan')
            ->columns([
                Split::make([
                    // Kiri: nomor WO, plat besar, kendaraan & pelanggan
                    Stack::make([
                        TextColumn::make('number')
                            ->formatStateUsing(fn (string $state, WorkOrder $record): string => $state.' · selesai '.Format::tanggalJam($record->finished_at))
                            ->size(TextColumn\TextColumnSize::ExtraSmall)
                            ->weight(FontWeight::SemiBold)
                            ->color('gray'),
                        TextColumn::make('vehicle.plate_number')
                            ->extraAttributes(['class' => 'bk-plate'])
                            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(fn (Builder $q) => $q
                                ->where('number', 'like', "%{$search}%")
                                ->orWhereHas('vehicle', fn (Builder $v) => $v->where('plate_number', 'like', "%{$search}%"))
                                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$search}%")))),
                        TextColumn::make('customer.name')
                            ->formatStateUsing(fn (?string $state, WorkOrder $record): string => trim(($record->vehicle?->brand ?? '').' '.($record->vehicle?->model ?? '')).' · '.$state)
                            ->color('gray'),
                    ])->space(1),
 
                    // Tengah: pekerjaan + sparepart + mekanik
                    Stack::make([
                        TextColumn::make('work_notes')
                            ->weight(FontWeight::SemiBold)
                            ->limit(90)
                            ->placeholder('Catatan pekerjaan kosong'),
                        TextColumn::make('mechanic.name')
                            ->formatStateUsing(fn (?string $state, WorkOrder $record): string => $this->itemsText($record).' · dikerjakan '.$state)
                            ->color('gray')
                            ->wrap(),
                    ])->space(1),
 
                    // Kanan: subtotal sparepart
                    TextColumn::make('parts_total')
                        ->default(0)
                        ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                        ->description('Sparepart', position: 'above')
                        ->weight(FontWeight::Bold)
                        ->alignEnd()
                        ->grow(false)
                        ->extraAttributes(['class' => 'tabular-nums']),
                ])->from('md'),
            ])
            ->actions([
                $this->makeBayarTableAction(),
            ])
            ->bulkActions([])
            // Antrean bayar biasanya pendek: tampil semua, tanpa pengaturan per halaman
            ->paginated(false)
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('Semua sudah dibayar')
            ->emptyStateDescription('Work Order yang selesai diservis akan muncul di sini.');
    }
 
    protected function itemsText(WorkOrder $record): string
    {
        if ($record->items->isEmpty()) {
            return 'Tanpa sparepart';
        }
 
        return $record->items
            ->map(fn (WorkOrderItem $item): string => ($item->sparepart?->name ?? 'Sparepart').' ×'.$item->qty)
            ->join(', ');
    }
 
    protected function makeBayarTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('bayar')
            ->label('Proses bayar')
            ->button()
            ->size(ActionSize::Large)
            ->color('primary')
            ->modalHeading(fn (WorkOrder $record): string => $record->vehicle?->plate_number ?? $record->number)
            ->modalDescription(fn (WorkOrder $record): string => $record->number.' · '.trim(($record->vehicle?->brand ?? '').' '.($record->vehicle?->model ?? '')).' · '.$record->customer?->name)
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Bayar')
            ->form(fn (WorkOrder $record): array => [
                Forms\Components\Placeholder::make('rincian')
                    ->hiddenLabel()
                    ->content(fn () => view('filament.pages.partials.rincian-bayar', [
                        'workOrder' => $record->loadMissing(['items.sparepart', 'mechanic']),
                    ])),
 
                Forms\Components\TextInput::make('service_fee')
                    ->label('Biaya jasa')
                    ->prefix('Rp')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(PaymentService::MAX_SERVICE_FEE)
                    ->required()
                    ->live(debounce: 500)
                    // Tandai pilihan cepat yang cocok (atau kosongkan kalau diketik manual)
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $fee = (int) $state;
                        $set('fee_preset', in_array($fee, self::FEE_PRESETS, true) ? (string) $fee : null);
                    })
                    ->validationMessages([
                        'required' => 'Biaya jasa wajib diisi (isi 0 kalau tidak ada).',
                        'min' => 'Biaya jasa tidak boleh negatif.',
                    ]),
 
                // Pilihan cepat: hanya mengisi kolom biaya jasa, tidak ikut dikirim
                Forms\Components\ToggleButtons::make('fee_preset')
                    ->hiddenLabel()
                    ->options(collect(self::FEE_PRESETS)
                        ->mapWithKeys(fn (int $fee): array => [(string) $fee => Format::rupiah($fee)])
                        ->all())
                    ->inline()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (filled($state)) {
                            $set('service_fee', (int) $state);
                        }
                    }),
 
                Forms\Components\ToggleButtons::make('method')
                    ->label('Metode')
                    // Opsi berupa array (bukan class enum) supaya pilihan aktif memakai warna
                    // perunggu tema, tidak ikut warna badge enum (hijau/biru).
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn (PaymentMethod $m): array => [$m->value => $m->getLabel()])
                        ->all())
                    ->default(PaymentMethod::Tunai->value)
                    ->grouped()
                    ->required(),
 
                Forms\Components\Placeholder::make('total')
                    ->label('Total bayar')
                    ->content(function (Get $get) use ($record): HtmlString {
                        $total = $record->sparepartTotal() + max(0, (int) $get('service_fee'));
 
                        return new HtmlString(
                            '<span class="bk-total">'.e(Format::rupiah($total)).'</span>'
                            .'<span class="mt-1 block text-xs text-gray-500">Total dihitung ulang oleh sistem saat disimpan.</span>'
                        );
                    }),
            ])
            ->action(function (WorkOrder $record, array $data, Tables\Actions\Action $action): void {
                /** @var Payment|null $payment */
                $payment = ServiceAction::run(
                    fn () => app(PaymentService::class)->pay($record, (int) $data['service_fee'], $data['method'], auth()->user()),
                    'Pembayaran berhasil',
                    'Nota siap dicetak.',
                    $action,
                );
 
                if ($payment instanceof Payment) {
                    $this->redirect(route('nota.show', $payment));
                }
            });
    }
}
 