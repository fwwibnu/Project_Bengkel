<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique(); // WO-YYYYMMDD-NNN
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('mechanic_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('complaint');                 // keluhan, diisi Admin
            $table->text('work_notes')->nullable();    // pekerjaan yang dilakukan, diisi Mekanik
            $table->string('status', 12)->default('baru'); // App\Enums\WorkOrderStatus
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['mechanic_id', 'status']);
        });

        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sparepart_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('unit_price'); // harga jual saat dicatat (dikunci)
            $table->unsignedBigInteger('subtotal');
            $table->timestamps();

            // Sparepart yang sama di satu WO digabung jadi satu baris
            $table->unique(['work_order_id', 'sparepart_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
        Schema::dropIfExists('work_orders');
    }
};
