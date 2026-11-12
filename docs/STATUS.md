# STATUS PROJECT — Sistem Informasi Pengelolaan Bengkel

Terakhir diperbarui: 12 November 2026
Stack: Laravel 12.71 + Filament 3.3.57 + Livewire 3 + Tailwind 3.4 + MySQL (Laragon)

---

## 1. Hasil audit awal (sebelum pekerjaan ini)

Isi project yang dikirim:

| Bagian | Kondisi awal |
|---|---|
| Laravel 12 + Filament 3.3 + Tailwind v3 | Sudah terpasang, tema Filament (`resources/css/filament/app`) sudah dibuat tapi masih kosong |
| Login | Halaman login kustom pakai **username**, pesan gagal umum, rate limit bawaan Filament |
| Role | Enum `Role` (admin, mekanik, kasir, pemilik) + kolom `users.role` + soft delete user |
| Panel | Satu panel `app` di `/app`, warna oranye, font Plus Jakarta Sans, nama + badge peran di topbar |
| Seeder | 7 akun demo (password `password`) |
| Data bengkel (pelanggan, kendaraan, mekanik, sparepart, WO, pembayaran, stok) | **Belum ada** (tidak ada migration, model, maupun halaman) |
| Halaman error 403/404 | **Belum ada** (Langkah 5b sebelumnya belum diterapkan) |
| Test | Hanya contoh bawaan Laravel |
| Git | `git init` sudah, belum ada commit |

Kesimpulan audit: F1 sebagian, F2–F12 belum ada.

---

## 2. Status fitur sekarang

| Kode | Fitur | Status | Lokasi utama |
|---|---|---|---|
| F1 | Autentikasi & multi-role | **Sudah** | `Filament/Pages/Auth/Login.php`, `Http/Responses/LoginResponse.php`, `Policies/*`, `AppServiceProvider` (Gate), `errors/403` |
| F2 | Dashboard | **Sudah** | `Filament/Pages/Dashboard.php`, view `filament/pages/dashboard.blade.php`, `Services/ReportService.php` |
| F3 | Sparepart + Tambah Stok | **Sudah** | `Filament/Resources/SparepartResource.php`, `Services/StockService.php` |
| F4 | Mekanik | **Sudah** | `Filament/Resources/MechanicResource.php`, `Services/MechanicService.php` |
| F5 | Pelanggan & kendaraan + riwayat | **Sudah** | `CustomerResource` (+ kendaraan), `VehicleResource` (detail + riwayat servis) |
| F6 | Work Order (Admin) | **Sudah** | `Filament/Resources/WorkOrderResource.php`, `Services/WorkOrderService.php`, `Services/NumberGenerator.php` |
| F7 | Pengerjaan mekanik (HP) | **Sudah** | `Filament/Pages/WorkOrderSaya.php` + view `filament/pages/work-order-saya` |
| F8 | Pembayaran & nota | **Sudah** | `Filament/Pages/Pembayaran.php`, `Services/PaymentService.php`, `NotaController`, view `print/nota` |
| F9 | Riwayat transaksi & servis | **Sudah** | `PaymentResource` (Riwayat Transaksi), `Filament/Pages/RiwayatServis.php`, riwayat di detail kendaraan |
| F10 | Laporan + cetak | **Sudah** | `Filament/Pages/Laporan.php`, `LaporanController`, view `print/laporan`, `laporan/_*.blade.php` |
| F11 | Akun pengguna | **Sudah** | `Filament/Resources/UserResource.php`, `Policies/UserPolicy.php` |
| F12 | Notifikasi toast | **Sudah** | Notifikasi Filament (pojok kanan atas) di semua aksi, lewat `Filament/Support/ServiceAction.php` |

Status uji (12 Nov 2026, di laptop Laragon): `php artisan test` → **38 passed (175 assertions)**. Uji manual end-to-end semua peran juga sudah dijalankan (lihat bagian 7).

---

## 3. Keputusan di kode yang beda dengan dokumen

Sesuai `ATURAN_PROJECT.md`, kalau kode berbeda dengan docs, kode yang diikuti.

| Dokumen | Di kode | Alasan / penyelesaian |
|---|---|---|
| PRD F1: login dengan **email** | Login dengan **username** (keputusan Fase 1) | Login **username ATAU email**. Kalau isian mengandung `@`, dicocokkan ke kolom email. Akun demo punya keduanya. |
| SYSTEM_DESIGN: `users.email` unik wajib | `email` opsional (nullable, unik) + `username` wajib unik | Dipertahankan dari kode. |
| SYSTEM_DESIGN: tabel `users` tanpa soft delete | `users` pakai soft delete | Dipertahankan. Akun yang dihapus tidak bisa login, tapi nama di riwayat WO, transaksi, dan nota tetap muncul. |
| Mode gelap (sempat disetujui di Fase 0) | Dimatikan (`->darkMode(false)`) | UI_GUIDE hanya mendefinisikan palet terang, dan halaman kustom (mekanik, laporan) dirancang untuk terang. Mencegah teks yang tidak terbaca. |

## 4. Keputusan baru yang diambil (tidak tertulis eksplisit di docs)

| Keputusan | Alasan |
|---|---|
| Tabel `payments` ditambah kolom `number` (`TRX-YYYYMMDD-NNN`) | Nota butuh nomor transaksi (desain + PDF). Dibuat otomatis seperti nomor WO. |
| `stock_movements` ditambah `work_order_id` (selain `reference`) | Laporan sirkulasi bisa menautkan pergerakan ke WO tanpa mencocokkan teks. |
| Kendaraan: kolom `model` boleh kosong, `type` = motor/mobil | Sesuai SYSTEM_DESIGN, tapi model tidak wajib supaya input cepat. |
| Satu mekanik satu WO aktif | Mekanik harus **Tersedia** saat dipilih (SYSTEM_DESIGN §4.3), jadi otomatis satu WO aktif. Status Sibuk/Tersedia dihitung ulang dari WO aktif (`MechanicService::refreshStatus`). |
| Status mekanik tidak ada di form edit | Sibuk/Tersedia otomatis. Libur diatur lewat tombol **Set Libur / Aktifkan** di daftar mekanik (ditolak kalau masih pegang WO aktif). |
| Sparepart yang sama dicatat dua kali → digabung, harga tetap harga pertama | SYSTEM_DESIGN §4.4 (boleh digabung) + aturan harga dikunci saat dicatat. |
| Stok tidak bisa diedit dari form sparepart | Stok hanya berubah lewat **Tambah Stok** dan pencatatan mekanik, supaya laporan sirkulasi selalu cocok. Stok awal saat membuat sparepart dicatat sebagai stok masuk "Stok awal". |
| Hapus data master ditolak kalau sudah dipakai | Sparepart (punya riwayat stok/pemakaian), pelanggan, kendaraan, mekanik yang sudah ada di WO → toast "tidak bisa dihapus, cukup diedit" + FK `restrict` di database. |
| Biaya jasa maksimal Rp 100.000.000 | Mencegah salah ketik nol berlebih. |
| Ekspor laporan = halaman siap cetak (Cetak / Simpan PDF dari browser) | Disepakati di Fase 0. Tidak ada package baru (ARCHITECTURE §7). CSV/Excel tidak dibuat. |
| Riwayat Servis untuk Kasir = halaman tersendiri (read-only) | Kasir tidak boleh mengelola kendaraan (PRD), tapi perlu melihat riwayat servis. |
| Riwayat Transaksi: Kasir & Admin. Nota: Kasir, Admin, Pemilik | Pemilik melihat transaksi lewat Laporan. Semua tetap read-only. |
| Admin tidak bisa mengubah perannya sendiri | Mencegah admin terkunci keluar dari menu Admin. |
| Zona waktu `Asia/Jakarta` | Jam di nota dan laporan sesuai WIB. |
| Identitas bengkel di `config/bengkel.php` (bisa lewat `.env`) | Nama, alamat, telepon di sidebar, nota, dan laporan tidak di-hardcode. |

## 5. Arsitektur yang dipakai

- **Satu panel Filament** (`/app`). Menu disaring per peran lewat Policy dan Gate. Akses lewat URL langsung dicek di server dan menghasilkan halaman **403** kustom.
- **Halaman awal per peran** (`App\Support\RoleHome`): Admin/Pemilik → Dashboard, Mekanik → Work Order Saya, Kasir → Pembayaran. Dipakai setelah login, saat membuka `/`, dan saat klik logo.
- **Services** (`app/Services`): semua perubahan status, stok, dan pembayaran hanya lewat sini. Masing-masing dibungkus `DB::transaction`, baris yang diubah dikunci `lockForUpdate()`, dan otorisasi dicek ulang lewat `Gate::forUser($user)->authorize(...)`.
  - `WorkOrderService`: buat, ubah, hapus, mulai, selesai, catatan pekerjaan
  - `StockService`: buat sparepart, Tambah Stok, catat & hapus sparepart terpakai
  - `PaymentService`: bayar (total dihitung ulang dari database, input browser hanya biaya jasa + metode)
  - `MechanicService`: Sibuk/Tersedia otomatis, Libur/Aktif
  - `NumberGenerator`: `WO-YYYYMMDD-NNN`, `TRX-YYYYMMDD-NNN`, `SP-NNN` (kunci baris + index unik + coba ulang 3x)
  - `ReportService`: dashboard & laporan (dikelompokkan di PHP supaya sama di MySQL dan SQLite)
- **Pelanggaran aturan bisnis** → `BusinessRuleException` dengan pesan Bahasa Indonesia → toast merah (`ServiceAction`). Modal tetap terbuka supaya isian tidak hilang.
- **Policies**: satu per model. Aksi massal, restore, dan force delete selalu ditolak (`DenyByDefault`).
- **Blade + Tailwind**: nota (A5), laporan cetak (A4), halaman error (403/404/419/500).

## 6. Rencana kerja (yang sudah dijalankan)

1. Audit project & dokumen → bagian 1.
2. Backend: enums, 4 migration baru, 8 model, 6 service, 8 policy, factories, seeder demo 6 bulan.
3. UI: tema kustom (sidebar gelap, kartu, badge), 7 resource, 5 halaman kustom, login split, tampilan HP mekanik, nota, 403/404/419/500.
4. Dashboard & laporan: dashboard kustom (pendapatan bulan ini, grafik 6 bulan, ringkasan, mekanik, stok menipis), laporan pendapatan/servis/sirkulasi stok + cetak.
5. QA: 7 file feature test (± 40 skenario) sesuai ARCHITECTURE §8.
6. Review: otorisasi server per peran, aturan bisnis, validasi, teks Indonesia, tidak ada `dd()`/`dump()`.

## 7. Pengujian

Jalankan: `php artisan test`. Hasil terakhir (12 Nov 2026): **38 passed, 175 assertions, ±4 detik**.

Uji manual end-to-end (12 Nov 2026), semua sesuai:
- Admin buat WO (hanya mekanik Tersedia yang bisa dipilih) → Mekanik mulai, catat sparepart (stok kurang ditolak dengan pesan jelas), hapus lalu catat ulang (tercatat Keluar/Batal/Keluar), tandai selesai (catatan wajib) → Kasir proses bayar (total = sparepart + jasa) → nota A5 + cetak → Riwayat Transaksi & Riwayat Servis.
- Admin Tambah Stok barang habis → badge berubah, tercatat Masuk di sirkulasi stok. Angka laporan sirkulasi, pendapatan, dan dashboard saling cocok.
- Pemilik hanya melihat Dashboard & Laporan; buka URL Sparepart langsung → 403 kustom. URL salah → 404 kustom.

Perbaikan setelah uji di laptop:
- `package.json`: `overrides` rollup → `@rollup/wasm-node` (Smart App Control Windows memblokir file `.node` bawaan rollup).
- `RevenueChart`: batang "Jumlah servis" tanpa garis tepi oranye.
- `WorkOrderSaya`: cek stok saat catat sparepart dipindah ke validasi server (pesan "Stok tidak cukup. Sisa stok …"), bukan atribut `max` browser.

| File | Yang diuji |
|---|---|
| `AuthAndAccessTest` | Login & redirect per peran, login pakai email, pesan salah password, akses URL langsung per peran (403), halaman cetak & nota terlindungi |
| `WorkOrderFlowTest` | Nomor WO, mekanik Sibuk/Tersedia, mekanik Libur/Sibuk ditolak, kendaraan harus milik pelanggan, hanya Admin buat WO, alur status, transisi ilegal ditolak, edit/hapus hanya saat Baru |
| `StockTest` | Stok berkurang + harga terkunci, stok kurang ditolak tanpa perubahan, gabung item, catat hanya saat Dikerjakan, hapus item mengembalikan stok, hapus setelah Selesai ditolak, Tambah Stok tercatat, hanya Admin tambah stok, kode SP otomatis |
| `PaymentTest` | Total benar & WO → Dibayar, tidak bisa bayar dua kali, WO belum selesai ditolak, biaya jasa negatif ditolak, hanya Kasir yang bisa bayar |
| `ReportTest` | Sirkulasi stok (masuk/keluar/batal/awal/akhir), ringkasan pendapatan, tren 6 bulan |
| `PanelActionsTest` | Aksi "Catat sparepart" di halaman mekanik & "Proses Bayar" di halaman kasir |
| `ExampleTest` | `/` mengarah ke login |

Pemilik tidak bisa mengubah data apa pun: dicek di `AuthAndAccessTest` (403 di semua halaman ubah), `WorkOrderFlowTest` (buat WO ditolak), `StockTest` (Tambah Stok ditolak), dan `PaymentTest` (bayar ditolak).

---

## 8. Bagian laporan PDF yang perlu direvisi (bahan laporan UAS)

| Bagian PDF | Tertulis di PDF | Yang benar (sesuai web) |
|---|---|---|
| II. Fitur — Autentikasi | "Autentikasi & Manajemen Pengguna" (tidak menyebut cara daftar) | Tidak ada pendaftaran mandiri. Akun dibuat Admin di menu Akun Pengguna. Login pakai username atau email. Admin bisa reset password. |
| II. Fitur — Dashboard | Statistik stok, stok menipis, mekanik tersedia/sibuk | Tambahkan: status **Libur**, grafik tren pendapatan & jumlah servis 6 bulan, grafik performa mekanik (WO selesai bulan ini), tabel stok menipis. |
| II. Fitur — Mekanik | Status "diperbarui secara dinamis" | Sibuk/Tersedia **otomatis** dari Work Order. Libur diatur Admin (tidak bisa kalau masih pegang WO aktif). |
| II. Fitur — "Pengelolaan Transaksi Servis & **Penjualan**" | Ada kata "penjualan" | Tidak ada penjualan sparepart lepas. Semua transaksi lewat **Work Order**. Ganti judul jadi "Pengelolaan Work Order". |
| II. Fitur — Sparepart | Tidak ada cara menambah stok | Tambahkan: aksi **Tambah Stok** oleh Admin, tercatat di laporan sirkulasi stok. |
| II. Fitur — Laporan | "dapat dipantau" | Tambahkan: bisa **dicetak / disimpan PDF** dari halaman laporan. |
| III.I Tahapan Workflow | Gambar kosong | Pakai diagram workflow (.drawio) yang sudah direvisi. |
| III.II langkah 1 | "Pendaftaran dan Autentikasi" | "Autentikasi". Tidak ada pendaftaran, akun dibuat Admin. |
| III.II langkah 2 | "Admin/Kasir menginput data pelanggan" | **Hanya Admin** yang menginput pelanggan & kendaraan. |
| III.II langkah 3 | Admin membuat WO dan menentukan mekanik | Tambahkan: hanya mekanik berstatus **Tersedia** yang bisa dipilih. Nomor WO otomatis `WO-YYYYMMDD-NNN`. Mekanik otomatis jadi Sibuk. |
| III.II langkah 4 | Mekanik update status & tambah sparepart | Urutan status: **Baru → Sedang Dikerjakan → Selesai → Dibayar**. Sparepart hanya bisa dicatat saat Sedang Dikerjakan. Harga sparepart dikunci saat dicatat. Stok kurang → ditolak. |
| III.II langkah 5 | Kasir menghitung total | **Kasir mengisi biaya jasa**. Total = subtotal sparepart + biaya jasa, **dihitung ulang oleh sistem**. Metode Tunai / Non-tunai. Status otomatis jadi Dibayar. |
| III.II langkah 6 | "Sistem mengurangi stok" (setelah pembayaran) | Stok berkurang **saat mekanik mencatat sparepart** (langkah 4), bukan saat pembayaran. Riwayat servis diambil dari data Work Order. |
| III.II langkah 7 | Pemilik memantau | Admin juga bisa melihat dashboard & laporan. Pemilik **hanya lihat** (tidak bisa mengubah data). |
| IV.I Aktor — Admin | "mengatur **rute** pengguna" | "mengelola **akun** dan peran pengguna" (salah ketik). |
| IV.I Aktor — Mekanik | "menerima perintah kerja" | Tambahkan: hanya melihat WO miliknya sendiri, bisa menghapus sparepart salah catat selama Sedang Dikerjakan. |
| IV.I Aktor — Kasir | "melihat riwayat **penjualan** dan servis" | "melihat riwayat **transaksi** dan servis" (hanya baca). Kasir tidak membuat WO. |
| IV.I Aktor — Pemilik | "mengunduh laporan bulanan/tahunan" | Mencetak / menyimpan PDF laporan pendapatan (harian/bulanan, rentang tanggal bebas), transaksi servis, dan sirkulasi stok. |
| IV.II Diagram Use Case | Gambar rusak ("Invalid or unsupported diagram") | Pakai use case diagram revisi (.drawio). |
| IV.III — Kelola Data Master | Satu use case "Kelola Data Master" | Dipecah: **Kelola Pelanggan & Kendaraan**, **Kelola Sparepart**, **Kelola Mekanik**, **Kelola Akun Pengguna**. |
| IV.III — Kelola Order & Penugasan | Aktor: Admin, **Kasir** | Aktor: **Admin saja**. |
| IV.III — Update Status & Input Sparepart | Satu use case | Dua use case terpisah: **Update Status Perbaikan** dan **Input Sparepart Terpakai** (sesuai diagram revisi). |
| IV.III — Terima Pembayaran & Cetak Nota | Satu use case | Dua use case: **Hitung & Terima Pembayaran** dan **Cetak Nota Transaksi**. |
| IV.III — Lihat Dashboard & Cetak Laporan | "mengekspor laporan" | "mencetak / menyimpan PDF laporan". |
| (Baru) Bab teknologi / implementasi | Belum ada | Tambahkan: Laravel 12, Filament 3, Tailwind CSS, MySQL. Keamanan: password di-hash, CSRF, validasi server, otorisasi per peran di server, transaksi database untuk stok & pembayaran. |

---

## 9. Tampilan baru "Gading & Perunggu" (13 Nov 2026)

Dipilih dari rancangan visual (4 pilihan tema dibandingkan langsung). Hanya tampilan yang berubah; logika, Service, Policy, dan tes tidak disentuh.

| Bagian | Isi |
|---|---|
| Warna & font | Latar gading `#F7F5F0`, kartu putih, aksen perunggu `#8C5A2B` (tombol), netral Stone, font Inter. Halaman cetak & error ikut warna yang sama. |
| Panel | Sidebar & topbar transparan berkaca (blur), sidebar selalu terbuka dengan nama menu, logo ikon + nama bengkel. |
| Pindah halaman | Mode SPA Filament (`->spa()`): tanpa reload. Nota & cetak laporan dikecualikan (`spaUrlExceptions`). |
| Animasi | Isi halaman naik berurutan, kartu terangkat saat disorot, tombol mengempis saat ditekan, modal muncul dengan latar blur. Mati otomatis jika OS diset "kurangi gerakan". |
| Dashboard | Ditata ulang: sapaan sesuai jam, pendapatan bulan ini (angka berjalan), grafik batang 6 bulan, 4 kartu ringkas, daftar mekanik + status, stok yang perlu dibeli. Widget bawaan Filament dihapus. |
| Pembayaran | Tiap Work Order tampil sebagai kartu, ringkasan "menunggu" & "masuk hari ini", modal bayar dengan pilihan cepat biaya jasa dan tombol metode Tunai/Non-tunai. |
