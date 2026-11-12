<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sparepart extends Model
{
    use HasFactory;

    /**
     * Kolom "stock" sengaja TIDAK ada di fillable:
     * stok hanya boleh berubah lewat App\Services\StockService
     * (Tambah Stok, catat/hapus sparepart di Work Order).
     */
    protected $fillable = [
        'code',
        'name',
        'min_stock',
        'purchase_price',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'min_stock' => 'integer',
            'purchase_price' => 'integer',
            'selling_price' => 'integer',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function workOrderItems(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /** Stok habis atau di bawah/sama dengan batas minimal. */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    /** Label kondisi stok untuk badge: Habis / Menipis / null (aman). */
    public function stockLabel(): ?string
    {
        if ($this->isOutOfStock()) {
            return 'Habis';
        }

        return $this->isLowStock() ? 'Menipis' : null;
    }

    public function isUsed(): bool
    {
        return $this->workOrderItems()->exists();
    }
}
