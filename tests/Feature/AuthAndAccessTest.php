<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Pembayaran;
use App\Filament\Pages\WorkOrderSaya;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesBengkelData;
use Tests\TestCase;

/** F1: login, redirect per peran, dan akses lewat URL langsung (403). */
class AuthAndAccessTest extends TestCase
{
    use CreatesBengkelData;
    use RefreshDatabase;

    public function test_login_mengarahkan_ke_halaman_awal_sesuai_peran(): void
    {
        $cases = [
            [User::factory()->admin()->create(['username' => 'admin1']), Dashboard::getUrl()],
            [User::factory()->pemilik()->create(['username' => 'pemilik1']), Dashboard::getUrl()],
            [User::factory()->kasir()->create(['username' => 'kasir1']), Pembayaran::getUrl()],
            [$this->mechanic()->user, WorkOrderSaya::getUrl()],
        ];

        foreach ($cases as [$user, $expected]) {
            Livewire::test(Login::class)
                ->set('data.username', $user->username)
                ->set('data.password', 'password')
                ->call('authenticate')
                ->assertHasNoErrors()
                ->assertRedirect($expected);

            auth()->logout();
        }
    }

    public function test_login_bisa_pakai_email(): void
    {
        $user = User::factory()->admin()->create(['email' => 'admin@bengkel.test']);

        Livewire::test(Login::class)
            ->set('data.username', 'admin@bengkel.test')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_menampilkan_pesan_umum(): void
    {
        $user = User::factory()->admin()->create();

        Livewire::test(Login::class)
            ->set('data.username', $user->username)
            ->set('data.password', 'salah-sekali')
            ->call('authenticate')
            ->assertHasErrors(['data.username']);

        $this->assertGuest();
    }

    public function test_admin_hanya_bisa_membuka_halaman_admin(): void
    {
        $this->actingAs($this->admin());

        foreach (['/app', '/app/work-orders', '/app/work-orders/create', '/app/pelanggan', '/app/kendaraan', '/app/sparepart', '/app/mekanik', '/app/akun-pengguna', '/app/laporan', '/app/riwayat-transaksi', '/app/riwayat-servis'] as $url) {
            $this->get($url)->assertOk();
        }

        foreach (['/app/pembayaran', '/app/work-order-saya'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_mekanik_tidak_bisa_membuka_data_master_pembayaran_dan_laporan(): void
    {
        $this->actingAs($this->mechanic()->user);

        $this->get('/app/work-order-saya')->assertOk();

        foreach (['/app/work-orders', '/app/work-orders/create', '/app/pelanggan', '/app/kendaraan', '/app/sparepart', '/app/mekanik', '/app/akun-pengguna', '/app/pembayaran', '/app/riwayat-transaksi', '/app/riwayat-servis', '/app/laporan'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_kasir_hanya_bisa_membuka_halaman_kasir(): void
    {
        $this->actingAs($this->kasir());

        foreach (['/app/pembayaran', '/app/riwayat-transaksi', '/app/riwayat-servis'] as $url) {
            $this->get($url)->assertOk();
        }

        foreach (['/app/work-orders', '/app/work-orders/create', '/app/pelanggan', '/app/sparepart', '/app/mekanik', '/app/akun-pengguna', '/app/laporan', '/app/work-order-saya'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_pemilik_hanya_bisa_melihat_dashboard_dan_laporan(): void
    {
        $this->actingAs($this->pemilik());

        $this->get('/app')->assertOk();
        $this->get('/app/laporan')->assertOk();
        $this->get('/laporan/cetak?jenis=stok')->assertOk();

        foreach (['/app/work-orders', '/app/work-orders/create', '/app/pelanggan/create', '/app/sparepart', '/app/sparepart/create', '/app/mekanik', '/app/akun-pengguna', '/app/pembayaran', '/app/riwayat-transaksi', '/app/work-order-saya'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_halaman_cetak_dilindungi(): void
    {
        $this->get('/laporan/cetak')->assertRedirect(route('filament.app.auth.login'));

        $this->actingAs($this->kasir());
        $this->get('/laporan/cetak')->assertForbidden();
    }

    public function test_nota_hanya_untuk_kasir_admin_pemilik(): void
    {
        [$order] = $this->finishedOrder();
        $kasir = $this->kasir();
        $payment = app(\App\Services\PaymentService::class)->pay($order, 50_000, 'tunai', $kasir);

        $this->actingAs($kasir)->get(route('nota.show', $payment))->assertOk()->assertSee($payment->number);
        $this->actingAs($this->pemilik())->get(route('nota.show', $payment))->assertOk();
        $this->actingAs($this->mechanic()->user)->get(route('nota.show', $payment))->assertForbidden();
    }
}
