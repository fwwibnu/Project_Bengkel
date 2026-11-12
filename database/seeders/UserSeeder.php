<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo untuk lokal. Semua password: "password".
 * Login bisa pakai username ATAU email.
 * Aman dijalankan berulang (pakai updateOrCreate berdasarkan username).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['name' => 'Rina Astuti',  'username' => 'admin',   'email' => 'admin@bengkel.test',    'role' => Role::Admin],
            ['name' => 'Hadi Kusuma',  'username' => 'pemilik', 'email' => 'pemilik@bengkel.test',  'role' => Role::Pemilik],
            ['name' => 'Maya Fitri',   'username' => 'kasir',   'email' => 'kasir@bengkel.test',    'role' => Role::Kasir],
            ['name' => 'Andi Pratama', 'username' => 'andi',    'email' => 'mekanik1@bengkel.test', 'role' => Role::Mekanik],
            ['name' => 'Budi Santoso', 'username' => 'budi',    'email' => 'mekanik2@bengkel.test', 'role' => Role::Mekanik],
            ['name' => 'Rudi Hartono', 'username' => 'rudi',    'email' => 'mekanik3@bengkel.test', 'role' => Role::Mekanik],
            ['name' => 'Joko Susilo',  'username' => 'joko',    'email' => 'mekanik4@bengkel.test', 'role' => Role::Mekanik],
        ];

        foreach ($akun as $data) {
            User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $data['role'],
                    'password' => 'password', // di-hash otomatis lewat cast di model User
                ],
            );
        }
    }
}
