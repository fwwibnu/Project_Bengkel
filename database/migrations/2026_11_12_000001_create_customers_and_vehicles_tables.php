<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('address')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            // Kendaraan ikut terhapus kalau pelanggannya dihapus.
            // Pelanggan yang sudah punya Work Order dicegah dihapus di aplikasi + FK work_orders (restrict).
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('plate_number', 15)->unique(); // disimpan UPPERCASE, satu spasi
            $table->string('brand', 50);                  // merek, mis. Honda
            $table->string('model', 50)->nullable();      // mis. Vario 125
            $table->string('type', 10)->default('motor'); // App\Enums\VehicleType
            $table->unsignedSmallInteger('year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};
