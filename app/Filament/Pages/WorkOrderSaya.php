<?php
 
namespace App\Filament\Pages;
 
use App\Enums\WorkOrderStatus;
use App\Filament\Support\ServiceAction;
use App\Models\Mechanic;
use App\Models\Sparepart;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
 
/**
 * Halaman Mekanik (HP-first): daftar Work Order miliknya + detail untuk
 * mulai, catat/hapus sparepart, isi catatan pekerjaan, dan tandai selesai.
 * Data dibatasi di query (hanya WO milik mekanik yang login), bukan hanya disembunyikan.
 */
class WorkOrderSaya extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
 
    protected static ?string $navigationLabel = 'Work Order Saya';
 
    protected static ?string $title = 'Work Order Saya';
 
    protected static ?string $slug = 'work-order-saya';
 
    protected static ?int $navigationSort = -1;
 
    protected static string $view = 'filament.pages.work-order-saya';
 
    /** ID Work Order yang sedang dibuka (?wo=ID). Kosong = tampilan daftar. */
    #[Url]
    public ?int $wo = null;
 
    public static function canAccess(): bool
    {
        return Gate::allows('akses-wo-saya');
    }
 
    public function getTitle(): string|Htmlable
    {
        return $this->wo ? 'Detail Work Order' : 'Work Order Saya';
    }
 
    public function getSubheading(): ?string
    {
        return $this->wo ? null : 'Hanya Work Order yang ditugaskan ke kamu.';
    }
 
    public function getMechanic(): ?Mechanic
    {
        return auth()->user()?->mechanic;
    }
 
    /** WO aktif (Baru / Sedang Dikerjakan), yang sedang dikerjakan di atas. */
    public function getActiveOrders(): Collection
    {
        return $this->ownQuery()
            ->active()
            ->with(['vehicle', 'customer'])
            ->withCount('items')
            ->get()
            ->sortBy(fn (WorkOrder $wo) => $wo->status === WorkOrderStatus::Dikerjakan ? 0 : 1)
            ->values();
    }
 
    /** Riwayat singkat: 10 WO terakhir yang sudah selesai / dibayar. */
    public function getHistory(): Collection
    {
        return $this->ownQuery()
            ->whereIn('status', [WorkOrderStatus::Selesai->value, WorkOrderStatus::Dibayar->value])
            ->with('vehicle')
            ->latest('finished_at')
            ->limit(10)
            ->get();
    }
 
    public function getSelected(): ?WorkOrder
    {
        if (! $this->wo) {
            return null;
        }
 
        return $this->ownQuery()
            ->with(['vehicle', 'customer', 'items.sparepart'])
            ->find($this->wo);
    }
 
    public function open(int $id): void
    {
        $this->wo = $id;
    }
 
    public function back(): void
    {
        $this->wo = null;
    }
 
    /* ---------------------------------------------------------------
     | Aksi (semua lewat Service, dicek ulang di server)
     |---------------------------------------------------------------*/
 
    public function mulaiAction(): Action
    {
        return Action::make('mulai')
            ->label('Mulai Kerjakan')
            ->icon('heroicon-m-play')
            ->color('primary')
            ->size(ActionSize::Large)
            ->extraAttributes(['class' => 'w-full'])
            ->requiresConfirmation()
            ->modalIcon('heroicon-o-play')
            ->modalHeading('Mulai kerjakan Work Order ini?')
            ->modalDescription('Status berubah menjadi Sedang Dikerjakan. Setelah itu kamu bisa mencatat sparepart.')
            ->modalSubmitActionLabel('Ya, mulai')
            ->action(function (array $arguments): void {
                $workOrder = $this->findOwn($arguments['wo'] ?? null);
 
                ServiceAction::run(
                    fn () => app(WorkOrderService::class)->start($workOrder, auth()->user()),
                    'Status: Sedang Dikerjakan',
                    $workOrder->number.' mulai dikerjakan.',
                );
 
                $this->wo = $workOrder->getKey();
            });
    }
 
    public function selesaiAction(): Action
    {
        return Action::make('selesai')
            ->label('Tandai Selesai')
            ->icon('heroicon-m-check')
            ->color('success')
            ->size(ActionSize::Large)
            ->extraAttributes(['class' => 'w-full'])
            ->modalIcon('heroicon-o-check-circle')
            ->modalHeading('Tandai Work Order selesai?')
            ->modalDescription('Setelah selesai, sparepart tidak bisa diubah lagi dan Work Order diteruskan ke Kasir.')
            ->modalSubmitActionLabel('Ya, selesai')
            ->fillForm(fn (array $arguments): array => [
                'work_notes' => $this->findOwn($arguments['wo'] ?? null)->work_notes,
            ])
            ->form([
                Forms\Components\Textarea::make('work_notes')
                    ->label('Pekerjaan yang dilakukan')
                    ->placeholder('Mis. Ganti oli mesin dan filter oli, bersihkan CVT.')
                    ->required()
                    ->rows(3)
                    ->maxLength(2000)
                    ->validationMessages(['required' => 'Tulis singkat pekerjaan yang sudah dilakukan.']),
            ])
            ->action(function (array $data, array $arguments, Action $action): void {
                $workOrder = $this->findOwn($arguments['wo'] ?? null);
 
                $ok = ServiceAction::run(
                    fn () => app(WorkOrderService::class)->finish($workOrder, auth()->user(), $data['work_notes'] ?? null),
                    'Work Order selesai',
                    $workOrder->number.' diteruskan ke Kasir. Kamu kembali Tersedia.',
                    $action,
                );
 
                if ($ok) {
                    $this->wo = null;
                }
            });
    }
 
    public function catatanAction(): Action
    {
        return Action::make('catatan')
            ->label('Catatan pekerjaan')
            ->icon('heroicon-m-pencil-square')
            ->color('gray')
            ->modalHeading('Catatan pekerjaan')
            ->modalSubmitActionLabel('Simpan')
            ->fillForm(fn (array $arguments): array => [
                'work_notes' => $this->findOwn($arguments['wo'] ?? null)->work_notes,
            ])
            ->form([
                Forms\Components\Textarea::make('work_notes')
                    ->label('Pekerjaan yang dilakukan')
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (array $data, array $arguments, Action $action): void {
                $workOrder = $this->findOwn($arguments['wo'] ?? null);
 
                ServiceAction::run(
                    fn () => app(WorkOrderService::class)->updateNotes($workOrder, auth()->user(), $data['work_notes'] ?? null),
                    'Catatan disimpan',
                    null,
                    $action,
                );
            });
    }
 
    public function catatSparepartAction(): Action
    {
        return Action::make('catatSparepart')
            ->label('Catat sparepart')
            ->icon('heroicon-m-plus')
            ->color('primary')
            ->outlined()
            ->size(ActionSize::Large)
            ->extraAttributes(['class' => 'w-full'])
            ->modalHeading('Catat sparepart')
            ->modalDescription(fn (array $arguments): string => $this->findOwn($arguments['wo'] ?? null)->number.' · stok langsung berkurang saat disimpan.')
            ->modalSubmitActionLabel('Simpan')
            ->modalWidth('md')
            ->form([
                Forms\Components\Select::make('sparepart_id')
                    ->label('Sparepart')
                    ->placeholder('Ketik untuk mencari kode atau nama')
                    ->options(fn (): array => Sparepart::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Sparepart $s) => [$s->id => "{$s->name} · {$s->code} (stok {$s->stock})"])
                        ->all())
                    ->searchable()
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('qty')
                    ->label('Jumlah')
                    ->numeric()
                    ->integer()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    // Cek stok di server (bukan atribut "max" browser) supaya pesannya jelas
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            $sparepart = filled($get('sparepart_id')) ? Sparepart::find($get('sparepart_id')) : null;
 
                            if ($sparepart && (int) $value > $sparepart->stock) {
                                $fail("Stok tidak cukup. Sisa stok {$sparepart->name}: {$sparepart->stock}. Hubungi Admin untuk menambah stok.");
                            }
                        },
                    ])
                    ->helperText(function (Get $get): string {
                        $sparepart = filled($get('sparepart_id')) ? Sparepart::find($get('sparepart_id')) : null;
 
                        return $sparepart
                            ? "Sisa stok {$sparepart->name}: {$sparepart->stock}."
                            : 'Pilih sparepart dulu.';
                    })
                    ->validationMessages([
                        'min' => 'Jumlah minimal 1.',
                    ]),
            ])
            ->action(function (array $data, array $arguments, Action $action): void {
                $workOrder = $this->findOwn($arguments['wo'] ?? null);
 
                ServiceAction::run(
                    fn () => app(StockService::class)->recordUsage($workOrder, (int) $data['sparepart_id'], (int) $data['qty'], auth()->user()),
                    'Sparepart dicatat',
                    'Stok sudah dikurangi otomatis.',
                    $action,
                );
            });
    }
 
    public function hapusItemAction(): Action
    {
        return Action::make('hapusItem')
            ->label('Hapus')
            ->iconButton()
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->size(ActionSize::Large)
            ->requiresConfirmation()
            ->modalHeading('Hapus sparepart ini?')
            ->modalDescription('Stok akan dikembalikan. Hanya bisa selama Work Order masih Sedang Dikerjakan.')
            ->modalSubmitActionLabel('Ya, hapus')
            ->action(function (array $arguments): void {
                $item = WorkOrderItem::query()->find($arguments['item'] ?? null);
 
                if (! $item) {
                    return;
                }
 
                // Pastikan item milik WO mekanik ini (dicek ulang juga oleh Policy di Service)
                $this->findOwn($item->work_order_id);
 
                ServiceAction::run(
                    fn () => app(StockService::class)->removeItem($item, auth()->user()),
                    'Sparepart dihapus',
                    'Stok sudah dikembalikan.',
                );
            });
    }
 
    /* ---------------------------------------------------------------*/
 
    protected function ownQuery()
    {
        return WorkOrder::query()->forMechanicUser(auth()->user());
    }
 
    /** WO milik mekanik yang login; selain itu 404. */
    protected function findOwn(mixed $id): WorkOrder
    {
        return $this->ownQuery()->findOrFail((int) $id);
    }
}
 