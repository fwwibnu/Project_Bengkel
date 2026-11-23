<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    /** Ringkasan di bawah judul. */
    public function getSubheading(): ?string
    {
        $inShop = WorkOrder::query()->active()->distinct()->count('customer_id');

        return Customer::query()->count().' pelanggan · '.Vehicle::query()->count().' kendaraan terdaftar'
            .($inShop > 0 ? ' · '.$inShop.' sedang di bengkel' : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Pelanggan')->icon('heroicon-m-plus'),
        ];
    }
}
