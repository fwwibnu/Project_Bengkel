<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique(); // TRX-YYYYMMDD-NNN, tampil di nota
            // UNIQUE: satu Work Order hanya boleh satu pembayaran (cegah bayar dobel)
            $table->foreignId('work_order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('sparepart_total');
            $table->unsignedBigInteger('service_fee');
            $table->unsignedBigInteger('grand_total');
            $table->string('method', 12); // App\Enums\PaymentMethod
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->index('paid_at');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sparepart_id')->constrained()->restrictOnDelete();
            $table->string('type', 10); // App\Enums\StockMovementType
            $table->unsignedInteger('qty');
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 30)->nullable(); // mis. nomor WO
            $table->string('note')->nullable();          // keterangan Tambah Stok
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['sparepart_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('payments');
    }
};
