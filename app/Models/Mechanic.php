<?php

namespace App\Models;

use App\Enums\MechanicStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mechanic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'specialty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MechanicStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /** Work Order yang masih dipegang (Baru / Sedang Dikerjakan). */
    public function activeWorkOrders(): HasMany
    {
        return $this->workOrders()->whereIn('status', WorkOrderStatus::aktif());
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', MechanicStatus::Tersedia);
    }

    public function isAvailable(): bool
    {
        return $this->status === MechanicStatus::Tersedia;
    }

    public function isUsed(): bool
    {
        return $this->workOrders()->exists();
    }
}
