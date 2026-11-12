<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Isi database untuk demo lokal: akun per peran + data bengkel 6 bulan terakhir.
     * Jalankan: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
