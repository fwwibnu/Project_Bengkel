<?php

namespace App\Models;

use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'plate_number',
        'brand',
        'model',
        'type',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'year' => 'integer',
        ];
    }

    /**
     * Nomor polisi selalu disimpan UPPERCASE dengan satu spasi antarbagian,
     * supaya "b 1234  kzt" dan "B 1234 KZT" dianggap sama (kolom unik).
     */
    public static function normalizePlate(?string $plate): string
    {
        $plate = strtoupper(trim((string) $plate));

        return (string) preg_replace('/\s+/', ' ', $plate);
    }

    protected function plateNumber(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => static::normalizePlate($value),
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Riwayat servis = Work Order kendaraan ini (tanpa tabel terpisah). */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /** Label singkat untuk pilihan di form, mis. "B 1234 KZT · Honda Vario 125". */
    public function getLabel(): string
    {
        return trim($this->plate_number.' · '.$this->brand.' '.($this->model ?? ''));
    }

    public function isUsed(): bool
    {
        return $this->workOrders()->exists();
    }
}
