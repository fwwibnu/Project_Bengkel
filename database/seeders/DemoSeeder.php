<?php

namespace Database\Seeders;

use App\Enums\MechanicStatus;
use App\Enums\PaymentMethod;
use App\Enums\VehicleType;
use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\MechanicService;
use App\Services\PaymentService;
use App\Services\StockService;
use App\Services\WorkOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data demo bengkel 6 bulan terakhir.
 *
 * Semua transaksi dibuat LEWAT SERVICE (sama seperti saat aplikasi dipakai),
 * dengan jam "dimundurkan" memakai Carbon::setTestNow(). Hasilnya stok, pergerakan
 * stok, riwayat servis, pembayaran, dashboard, dan laporan saling konsisten.
 *
 * Keadaan akhir (untuk demo):
 * - Andi  : Tersedia        - Budi : Sibuk (WO Sedang Dikerjakan)
 * - Rudi  : Sibuk (WO Baru) - Joko : Libur
 * - 2 WO Selesai menunggu pembayaran Kasir
 * - 4 sparepart menipis/habis (Oli mesin, Busi, Filter udara, Kampas rem depan)
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, Sparepart> */
    protected array $parts = [];

    /** @var array<string, Mechanic> */
    protected array $mechanics = [];

    /** @var list<Vehicle> */
    protected array $vehicles = [];

    protected User $admin;

    protected User $kasir;

    public function __construct(
        protected WorkOrderService $workOrders,
        protected StockService $stock,
        protected PaymentService $payments,
        protected MechanicService $mechanicService,
    ) {}

    public function run(): void
    {
        mt_srand(20261112); // hasil acak selalu sama setiap seeding

        $realNow = Carbon::now();
        $this->admin = User::query()->where('username', 'admin')->firstOrFail();
        $this->kasir = User::query()->where('username', 'kasir')->firstOrFail();

        try {
            // Data master dibuat 5 bulan lalu (awal bulan), supaya "stok awal" masuk laporan sirkulasi
            $start = $realNow->copy()->subMonthsNoOverflow(5)->startOfMonth()->setTime(8, 0);
            Carbon::setTestNow($start);

            $this->seedMechanics();
            $this->seedSpareparts();
            $this->seedCustomers();

            // Riwayat servis lengkap (dibuat → dikerjakan → selesai → dibayar)
            foreach ($this->historyDates($realNow) as $i => $at) {
                $this->restockIfScheduled($i, $at);
                $this->fullService($i, $at, pay: true);
            }

            // Keadaan hari ini
            $this->seedToday($realNow);
        } finally {
            Carbon::setTestNow();
        }

        $this->mechanicService->setLibur($this->mechanics['joko'], $this->admin);
    }

    /* ---------------------------------------------------------------
     | Data master
     |---------------------------------------------------------------*/

    protected function seedMechanics(): void
    {
        $data = [
            'andi' => ['Andi Pratama', '0812-1111-2222', 'Kelistrikan, injeksi'],
            'budi' => ['Budi Santoso', '0813-3333-4444', 'Mesin, servis rutin'],
            'rudi' => ['Rudi Hartono', '0857-5555-6666', 'AC, kaki-kaki mobil'],
            'joko' => ['Joko Susilo', '0821-7777-8888', 'Body, rem'],
        ];

        foreach ($data as $username => [$name, $phone, $specialty]) {
            $user = User::query()->where('username', $username)->firstOrFail();

            $this->mechanics[$username] = Mechanic::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'specialty' => $specialty,
                    'status' => MechanicStatus::Tersedia,
                ],
            );
        }
    }

    protected function seedSpareparts(): void
    {
        // [nama, stok awal, stok minimal, harga beli, harga jual]  → kode dibuat otomatis SP-001 dst.
        $data = [
            ['Aki kering 12V', 20, 4, 210_000, 260_000],
            ['Oli mesin 1L', 12, 10, 42_000, 55_000],
            ['Oli gardan 120ml', 30, 6, 12_000, 18_000],
            ['Oli mesin mobil 4L', 20, 4, 180_000, 235_000],
            ['Filter oli', 30, 6, 25_000, 35_000],
            ['Kampas rem belakang', 25, 5, 40_000, 55_000],
            ['Kampas rem depan', 4, 5, 48_000, 65_000],
            ['V-belt CVT', 20, 3, 95_000, 130_000],
            ['Bohlam H4', 40, 8, 18_000, 30_000],
            ['Ban dalam 17"', 25, 5, 35_000, 50_000],
            ['Busi standar', 10, 8, 16_000, 25_000],
            ['Air radiator 1L', 30, 6, 15_000, 22_000],
            ['Minyak rem DOT 3', 25, 5, 20_000, 30_000],
            ['Filter udara', 6, 5, 32_000, 45_000],
            ['Rantai + gir set', 20, 3, 230_000, 295_000],
        ];

        foreach ($data as [$name, $stock, $min, $buy, $sell]) {
            $sparepart = $this->stock->createSparepart([
                'code' => null,
                'name' => $name,
                'stock' => $stock,
                'min_stock' => $min,
                'purchase_price' => $buy,
                'selling_price' => $sell,
            ], $this->admin);

            $this->parts[$sparepart->code] = $sparepart;
        }
    }

    protected function seedCustomers(): void
    {
        $data = [
            ['Dewi Lestari', '0812-3456-7890', 'Jl. Joe No. 3, Lenteng Agung', [
                ['B 1234 KZT', 'Honda', 'Vario 125', 'motor', 2021],
                ['B 2290 MNO', 'Honda', 'Scoopy', 'motor', 2019],
            ]],
            ['Agus Salim', '0813-2211-4455', 'Jl. Kebagusan Raya 12', [['B 5521 PQR', 'Yamaha', 'NMAX', 'motor', 2022]]],
            ['Siti Rahma', '0838-4455-6677', 'Jl. Jagakarsa 17', [['B 3088 TUV', 'Toyota', 'Avanza', 'mobil', 2018]]],
            ['Hendra Wijaya', '0857-1100-2233', 'Jl. Srengseng Sawah 8', [['B 7710 ABC', 'Honda', 'Beat', 'motor', 2020]]],
            ['Rizky Ramadhan', '0811-9090-1212', 'Jl. Tanjung Barat 45', [['B 9902 XYZ', 'Suzuki', 'Ertiga', 'mobil', 2019]]],
            ['Lina Marlina', '0821-7788-9900', 'Jl. Moh. Kahfi II 21', [['B 4417 DEF', 'Yamaha', 'Mio', 'motor', 2017]]],
            ['Yusuf Maulana', '0812-6630-1122', 'Jl. Ciganjur 5', [['B 6630 JKL', 'Daihatsu', 'Xenia', 'mobil', 2016]]],
            ['Fitri Handayani', '0896-3176-0099', 'Jl. Pasar Minggu 77', [['B 3176 RST', 'Honda', 'PCX', 'motor', 2023]]],
            ['Bayu Saputra', '0815-8812-3344', 'Jl. Kalibata Timur 9', [
                ['B 8812 UVW', 'Yamaha', 'Aerox', 'motor', 2021],
                ['B 1559 HJK', 'Honda', 'Brio', 'mobil', 2020],
            ]],
            ['Nur Aini', '0877-4021-5566', 'Jl. Pejaten Barat 14', [['B 4021 QWE', 'Suzuki', 'Nex II', 'motor', 2019]]],
        ];

        foreach ($data as [$name, $phone, $address, $vehicles]) {
            $customer = Customer::query()->create(['name' => $name, 'phone' => $phone, 'address' => $address]);

            foreach ($vehicles as [$plate, $brand, $model, $type, $year]) {
                $this->vehicles[] = $customer->vehicles()->create([
                    'plate_number' => $plate,
                    'brand' => $brand,
                    'model' => $model,
                    'type' => $type,
                    'year' => $year,
                ]);
            }
        }
    }

    /* ---------------------------------------------------------------
     | Riwayat
     |---------------------------------------------------------------*/

    /** Tanggal WO historis: 6–8 per bulan selama 6 bulan (bulan ini sampai kemarin). */
    protected function historyDates(Carbon $realNow): array
    {
        $perMonth = [6, 7, 7, 8, 8, 6];
        $dates = [];

        foreach ($perMonth as $index => $count) {
            $offset = 5 - $index;
            $month = $realNow->copy()->subMonthsNoOverflow($offset)->startOfMonth();
            $lastDay = $offset === 0 ? $realNow->day - 1 : $month->daysInMonth;

            if ($lastDay < 1) {
                continue;
            }

            $count = min($count, $lastDay);
            $slot = max(1, intdiv($lastDay, $count));

            for ($k = 0; $k < $count; $k++) {
                $day = min($lastDay, 1 + $k * $slot + mt_rand(0, $slot - 1));
                $dates[] = $month->copy()->setDay($day)->setTime(8 + mt_rand(0, 6), mt_rand(0, 3) * 15);
            }
        }

        usort($dates, fn (Carbon $a, Carbon $b) => $a <=> $b);

        return $dates;
    }

    protected function restockIfScheduled(int $i, Carbon $at): void
    {
        $plan = [
            12 => [['SP-005', 15], ['SP-009', 10]],
            28 => [['SP-001', 5], ['SP-006', 10]],
        ];

        if (! isset($plan[$i])) {
            return;
        }

        Carbon::setTestNow($at->copy()->subHour());

        foreach ($plan[$i] as [$code, $qty]) {
            $this->stock->addStock($this->parts[$code], $qty, 'Pembelian dari supplier', $this->admin);
        }
    }

    /** Satu servis lengkap lewat Service. */
    protected function fullService(int $i, Carbon $at, bool $pay, ?Vehicle $vehicle = null, ?string $mechanicKey = null, ?array $parts = null): WorkOrder
    {
        $vehicle ??= $this->vehicles[mt_rand(0, count($this->vehicles) - 1)];
        $mechanic = $this->mechanics[$mechanicKey ?? ['andi', 'budi', 'rudi', 'joko'][$i % 4]];
        $mechanicUser = $mechanic->user;
        [$complaint, $notes] = $this->complaintFor($vehicle);

        Carbon::setTestNow($at);
        $order = $this->workOrders->create([
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->getKey(),
            'mechanic_id' => $mechanic->getKey(),
            'complaint' => $complaint,
        ], $this->admin);

        Carbon::setTestNow($at->copy()->addMinutes(15));
        $this->workOrders->start($order, $mechanicUser);

        Carbon::setTestNow($at->copy()->addMinutes(45));
        foreach ($parts ?? $this->partsFor($i, $vehicle) as [$code, $qty]) {
            try {
                $this->stock->recordUsage($order, $this->parts[$code]->getKey(), $qty, $mechanicUser);
            } catch (BusinessRuleException) {
                // stok habis → lewati, sama seperti mekanik yang batal memakai sparepart itu
            }
        }

        Carbon::setTestNow($at->copy()->addMinutes(90 + mt_rand(0, 60)));
        $this->workOrders->finish($order, $mechanicUser, $notes);

        if ($pay) {
            Carbon::setTestNow(Carbon::now()->addMinutes(10 + mt_rand(0, 20)));
            $isCar = $vehicle->type === VehicleType::Mobil;
            $fees = $isCar ? [150_000, 200_000, 250_000] : [50_000, 75_000, 100_000, 125_000];

            $this->payments->pay(
                $order,
                $fees[mt_rand(0, count($fees) - 1)],
                mt_rand(1, 10) <= 7 ? PaymentMethod::Tunai : PaymentMethod::NonTunai,
                $this->kasir,
            );
        }

        return $order;
    }

    /**
     * Sparepart per WO historis: 1–2 sparepart umum sesuai jenis kendaraan,
     * ditambah jadwal pemakaian sparepart "menipis" supaya stok akhirnya rendah.
     *
     * @return list<array{0:string, 1:int}>
     */
    protected function partsFor(int $i, Vehicle $vehicle): array
    {
        $isCar = $vehicle->type === VehicleType::Mobil;
        $pool = $isCar
            ? ['SP-001', 'SP-004', 'SP-005', 'SP-012', 'SP-013']
            : ['SP-003', 'SP-005', 'SP-006', 'SP-008', 'SP-009', 'SP-010', 'SP-013', 'SP-015'];
        $expensive = ['SP-001', 'SP-004', 'SP-008', 'SP-015'];

        $parts = [];
        $picks = mt_rand(1, 2);
        $chosen = [];

        while (count($chosen) < $picks) {
            $code = $pool[mt_rand(0, count($pool) - 1)];
            $chosen[$code] = true;
        }

        foreach (array_keys($chosen) as $code) {
            $parts[] = [$code, in_array($code, $expensive, true) ? 1 : mt_rand(1, 2)];
        }

        $lowSchedule = [
            'SP-002' => [0, 4, 8, 12, 16, 20, 24, 28, 32], // Oli mesin 1L: 12 → 3
            'SP-011' => [1, 7, 13, 19, 25, 31],            // Busi: 10 → 4
            'SP-014' => [2, 10, 18, 26],                   // Filter udara: 6 → 2
            'SP-007' => [5, 21],                           // Kampas rem depan: 4 → 2 (habis oleh WO hari ini)
        ];

        foreach ($lowSchedule as $code => $indexes) {
            if (in_array($i, $indexes, true)) {
                $parts[] = [$code, 1];
            }
        }

        return $parts;
    }

    /** @return array{0:string, 1:string} [keluhan, pekerjaan] */
    protected function complaintFor(Vehicle $vehicle): array
    {
        $motor = [
            ['Ganti oli dan servis rutin', 'Ganti oli mesin, cek rem, setel rantai.'],
            ['Tarikan terasa berat', 'Bersihkan CVT, ganti part yang aus.'],
            ['Rem kurang pakem', 'Ganti kampas rem, tambah minyak rem.'],
            ['Mesin susah hidup pagi hari', 'Cek kelistrikan, bersihkan injektor.'],
            ['Lampu depan mati', 'Ganti bohlam lampu depan, cek soket.'],
            ['Ban sering kempes', 'Ganti ban dalam, cek tekanan angin.'],
        ];

        $mobil = [
            ['Servis berkala 10.000 km', 'Ganti oli mesin dan filter, cek radiator dan rem.'],
            ['AC kurang dingin', 'Bersihkan evaporator, cek tekanan freon.'],
            ['Mesin cepat panas', 'Kuras radiator, ganti air radiator.'],
            ['Aki tekor', 'Ganti aki, cek sistem pengisian.'],
            ['Rem bunyi saat mengerem', 'Bersihkan rem, ganti minyak rem.'],
        ];

        $list = $vehicle->type === VehicleType::Mobil ? $mobil : $motor;

        return $list[mt_rand(0, count($list) - 1)];
    }

    /* ---------------------------------------------------------------
     | Hari ini
     |---------------------------------------------------------------*/

    protected function seedToday(Carbon $realNow): void
    {
        $base = $realNow->copy()->subMinutes(300);
        $byPlate = collect($this->vehicles)->keyBy('plate_number');

        // A: sudah dibayar hari ini (Andi)
        $this->fullService(100, $base->copy(), pay: true, vehicle: $byPlate['B 5521 PQR'], mechanicKey: 'andi', parts: [['SP-003', 1], ['SP-005', 1]]);

        // B: Selesai, menunggu Kasir (Budi)
        $this->fullService(101, $base->copy()->addMinutes(5), pay: false, vehicle: $byPlate['B 7710 ABC'], mechanicKey: 'budi', parts: [['SP-009', 1], ['SP-013', 1]]);

        // C: Selesai, menunggu Kasir (Andi, setelah WO A selesai)
        $this->fullService(102, $base->copy()->addMinutes(160), pay: false, vehicle: $byPlate['B 6630 JKL'], mechanicKey: 'andi', parts: [['SP-012', 2], ['SP-005', 1]]);

        // D: Sedang Dikerjakan (Budi) — pakai 2 kampas rem depan terakhir → stok habis
        Carbon::setTestNow($base->copy()->addMinutes(170));
        $budi = $this->mechanics['budi'];
        $order = $this->workOrders->create([
            'customer_id' => $byPlate['B 1234 KZT']->customer_id,
            'vehicle_id' => $byPlate['B 1234 KZT']->getKey(),
            'mechanic_id' => $budi->getKey(),
            'complaint' => 'Rem depan bunyi saat mengerem pelan',
        ], $this->admin);

        Carbon::setTestNow($base->copy()->addMinutes(185));
        $this->workOrders->start($order, $budi->user);

        Carbon::setTestNow($base->copy()->addMinutes(200));
        try {
            $this->stock->recordUsage($order, $this->parts['SP-007']->getKey(), 2, $budi->user);
        } catch (BusinessRuleException) {
            //
        }
        $this->workOrders->updateNotes($order, $budi->user, 'Bongkar rem depan, ganti kampas.');

        // E: Baru, belum dikerjakan (Rudi)
        Carbon::setTestNow($base->copy()->addMinutes(240));
        $this->workOrders->create([
            'customer_id' => $byPlate['B 3088 TUV']->customer_id,
            'vehicle_id' => $byPlate['B 3088 TUV']->getKey(),
            'mechanic_id' => $this->mechanics['rudi']->getKey(),
            'complaint' => 'AC kurang dingin, kadang keluar angin panas',
        ], $this->admin);
    }
}
