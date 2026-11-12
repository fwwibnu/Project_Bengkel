<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Work Order. Kolom status/started_at/finished_at/paid_at hanya diubah
 * lewat App\Services\WorkOrderService dan App\Services\PaymentService.
 */
class WorkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'vehicle_id',
        'mechanic_id',
        'created_by',
        'complaint',
        'work_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => 'baru',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function scopeStatus(Builder $query, WorkOrderStatus ...$statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', WorkOrderStatus::aktif());
    }

    public function scopeForMechanicUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('mechanic', fn (Builder $q) => $q->where('user_id', $user->getKey()));
    }

    public function sparepartTotal(): int
    {
        return (int) $this->items()->sum('subtotal');
    }

    public function hasStatus(WorkOrderStatus ...$statuses): bool
    {
        return in_array($this->status, $statuses, true);
    }

    /** Edit/hapus hanya saat Baru dan belum ada sparepart. */
    public function isEditable(): bool
    {
        return $this->status === WorkOrderStatus::Baru && ! $this->items()->exists();
    }
}
