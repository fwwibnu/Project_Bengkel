<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pembayaran Work Order. Dibuat hanya lewat App\Services\PaymentService. */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'work_order_id',
        'cashier_id',
        'sparepart_total',
        'service_fee',
        'grand_total',
        'method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'sparepart_total' => 'integer',
            'service_fee' => 'integer',
            'grand_total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id')->withTrashed();
    }
}
