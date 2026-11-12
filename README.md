# Sistem Informasi Pengelolaan Bengkel

Tugas kelompok 1 — Pemrograman Berbasis Web, Universitas Pancasila.
Laravel 12 · Filament 3 · Tailwind CSS 3 · MySQL (Laragon).

## Kebutuhan

- PHP 8.2+ dengan ekstensi `intl` dan `zip` aktif
- Composer 2
- Node.js 18+ dan npm
- MySQL (bawaan Laragon)

## Instalasi di Laragon (Windows)

```bash
cd C:\laragon\www\bengkel
composer install
copy .env.example .env          # lewati kalau .env sudah ada
php artisan key:generate
```

Buat database (Laragon → Start All dulu):

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS bengkel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Isi tabel + data demo, lalu build tampilan:

```bash
php artisan migrate:fresh --seed
npm install
npm run build
```

Jalankan:

```bash
php artisan serve
```

Buka http://127.0.0.1:8000 (atau http://bengkel.test kalau pakai virtual host Laragon).

> Kalau `npm` diblokir di PowerShell VS Code, pakai terminal **Command Prompt**.

## Akun demo (khusus lokal)

Password semua akun: `password`. Login bisa pakai username atau email.

| Peran | Username | Email | Halaman awal |
|---|---|---|---|
| Admin | `admin` | admin@bengkel.test | Dashboard |
| Pemilik Bengkel | `pemilik` | pemilik@bengkel.test | Dashboard |
| Kasir | `kasir` | kasir@bengkel.test | Pembayaran |
| Mekanik (sedang mengerjakan WO) | `budi` | mekanik2@bengkel.test | Work Order Saya |
| Mekanik (WO baru) | `rudi` | mekanik3@bengkel.test | Work Order Saya |
| Mekanik (tersedia) | `andi` | mekanik1@bengkel.test | Work Order Saya |
| Mekanik (libur) | `joko` | mekanik4@bengkel.test | Work Order Saya |

## Struktur folder penting

```
app/
  Enums/          Role, WorkOrderStatus, MechanicStatus, PaymentMethod, StockMovementType, VehicleType
  Models/         User, Customer, Vehicle, Mechanic, Sparepart, WorkOrder, WorkOrderItem, Payment, StockMovement
  Services/       WorkOrderService, StockService, PaymentService, MechanicService, ReportService, NumberGenerator
  Policies/       otorisasi per peran (dicek di server)
  Filament/
    Resources/    Work Order, Pelanggan, Kendaraan, Sparepart, Mekanik, Akun Pengguna, Riwayat Transaksi
    Pages/        Dashboard, Work Order Saya (mekanik), Pembayaran, Riwayat Servis, Laporan, Login
    Widgets/      kartu statistik, grafik pendapatan, performa mekanik, stok menipis
  Http/Controllers/  NotaController, LaporanController (halaman cetak)
resources/views/
  filament/       tampilan halaman kustom panel
  print/          nota & laporan siap cetak
  errors/         403, 404, 419, 500
docs/STATUS.md    status fitur, keputusan, dan daftar revisi laporan
```

## Pengujian

```bash
php artisan test
```

Test memakai SQLite di memori (lihat `phpunit.xml`), jadi tidak menyentuh database MySQL.
