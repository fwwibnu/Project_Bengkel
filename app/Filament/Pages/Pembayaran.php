<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Enums\WorkOrderStatus;
use App\Filament\Support\ServiceAction;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Services\PaymentService;
use App\Support\Format;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

/**
 * Kasir: pilih Work Order berstatus Selesai → isi biaya jasa & metode → bayar → nota.
 * Total dihitung ulang di server (PaymentService), browser hanya mengirim biaya jasa & metode.
 */
class Pembayaran extends Page implements HasTable
{
    use InteractsWithTable;

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
        return 'Work Order yang servisnya sudah selesai dan menunggu pembayaran.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()
                    ->where('status', WorkOrderStatus::Selesai->value)
                    ->with(['vehicle', 'customer', 'mechanic'])
                    ->withSum('items as parts_total', 'subtotal')
            )
            ->defaultSort('finished_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('No. WO')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (WorkOrder $record): string => 'Selesai '.Format::tanggalJam($record->finished_at)),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Kendaraan')
                    ->searchable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (WorkOrder $record): string => trim(($record->vehicle?->brand ?? '').' '.($record->vehicle?->model ?? ''))),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mechanic.name')
                    ->label('Mekanik'),
                Tables\Columns\TextColumn::make('parts_total')
                    ->label('Subtotal sparepart')
                    ->formatStateUsing(fn ($state): string => Format::rupiah($state))
                    ->alignEnd()
                    ->extraAttributes(['class' => 'tabular-nums']),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->actions([
                $this->makeBayarTableAction(),
            ])
            ->bulkActions([])
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('Belum ada Work Order yang siap dibayar')
            ->emptyStateDescription('Work Order muncul di sini setelah mekanik menandainya Selesai.');
    }

    protected function makeBayarTableAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('bayar')
            ->label('Proses Bayar')
            ->icon('heroicon-m-banknotes')
            ->button()
            ->color('primary')
            ->modalHeading(fn (WorkOrder $record): string => 'Pembayaran '.$record->number)
            ->modalDescription(fn (WorkOrder $record): string => $record->vehicle?->getLabel().' · '.$record->customer?->name)
            ->modalWidth('2xl')
            ->modalSubmitActionLabel('Proses Pembayaran')
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
                    ->validationMessages([
                        'required' => 'Biaya jasa wajib diisi (isi 0 kalau tidak ada).',
                        'min' => 'Biaya jasa tidak boleh negatif.',
                    ]),

                Forms\Components\Radio::make('method')
                    ->label('Metode')
                    ->options(PaymentMethod::class)
                    ->default(PaymentMethod::Tunai->value)
                    ->inline()
                    ->inlineLabel(false)
                    ->required(),

                Forms\Components\Placeholder::make('total')
                    ->label('Total bayar')
                    ->content(function (Get $get) use ($record): HtmlString {
                        $total = $record->sparepartTotal() + max(0, (int) $get('service_fee'));

                        return new HtmlString(
                            '<span class="text-2xl font-bold tabular-nums text-gray-950">'.e(Format::rupiah($total)).'</span>'
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
