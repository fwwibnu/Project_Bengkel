<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            // Akun login (role mekanik). Boleh kosong, tapi tanpa akun mekanik tidak bisa membuka "Work Order Saya".
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('specialty', 100)->nullable(); // keahlian
            $table->string('status', 10)->default('tersedia'); // App\Enums\MechanicStatus
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();             // SP-001, dibuat otomatis kalau dikosongkan
            $table->string('name', 100);
            $table->unsignedInteger('stock')->default(0);     // tidak boleh negatif (unsigned + dicek di service)
            $table->unsignedInteger('min_stock')->default(0); // batas stok menipis
            $table->unsignedBigInteger('purchase_price')->default(0); // Rupiah, tanpa desimal
            $table->unsignedBigInteger('selling_price')->default(0);  // Rupiah, tanpa desimal
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spareparts');
        Schema::dropIfExists('mechanics');
    }
};
