<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAwalSeeder;
use Database\Seeders\IzinSeeder;
use Database\Seeders\TenantAwalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Penjaga agar data contoh tidak pernah masuk ke basis data produksi.
 *
 * Runbook sudah memperingatkan bahwa `php artisan db:seed` polos memanggil
 * DemoAwalSeeder, tetapi peringatan tertulis tidak menahan perintah yang
 * terlanjur diketik. Yang ditanamnya bukan data contoh biasa melainkan akun
 * Super Admin dengan kata sandi yang dapat ditebak.
 */
class SeederDataContohTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL_ADMIN_CONTOH = 'admin@amanpoll.test';

    /** Kode PT Sinar Nusantara Industri, perusahaan demo yang disemai DemoAwalSeeder. */
    private const KODE_ORGANISASI_CONTOH = 'SNI';

    protected function setUp(): void
    {
        parent::setUp();

        // Yang dijaga di sini hanya akun dan organisasi contoh; riwayat setahun diuji
        // sekali di SeederUnitPengelolaTest karena memakan waktu ± 2,5 menit.
        config(['amanpoll.demo.riwayat' => false]);
    }

    private function jadikanProduksi(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');
    }

    private function jumlahOrganisasiContoh(): int
    {
        return DB::table('Organisasi')->where('Kode', self::KODE_ORGANISASI_CONTOH)->count();
    }

    private function jumlahAdminContoh(): int
    {
        return DB::table('Pengguna')->where('Email', self::EMAIL_ADMIN_CONTOH)->count();
    }

    public function test_seeder_demo_menolak_dijalankan_di_produksi(): void
    {
        $this->jadikanProduksi();

        try {
            // Persis perintah yang diketik saat deployment; `--force` melewati
            // konfirmasi bawaan Laravel, jadi penjaganya tidak boleh bergantung
            // pada prompt itu.
            $this->artisan('db:seed', ['--class' => DemoAwalSeeder::class, '--force' => true]);
            $this->fail('DemoAwalSeeder seharusnya menolak berjalan di produksi.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('produksi', $e->getMessage());
        }

        // Ditolak di muka tidak sama dengan tidak tertulis; yang dijaga adalah
        // yang kedua.
        $this->assertSame(0, $this->jumlahOrganisasiContoh());
        $this->assertSame(0, $this->jumlahAdminContoh());
    }

    public function test_database_seeder_di_produksi_menyemai_kunci_wajib_tanpa_akun_contoh(): void
    {
        $this->jadikanProduksi();

        // Harus keluar bersih: penyemaian kunci wajib di produksi tidak boleh
        // tampak gagal hanya karena data contohnya dilewati.
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        // Pembanding: kunci wajibnya memang tersemai, jadi nol di bawah bukan
        // karena seluruh penyemaian tidak berjalan.
        $this->assertGreaterThan(0, DB::table('Izin')->count(), 'Izin wajib tetap harus tersemai di produksi.');
        $this->assertGreaterThan(0, DB::table('FiturPaket')->count(), 'FiturPaket wajib tetap harus tersemai di produksi.');

        $this->assertSame(0, $this->jumlahOrganisasiContoh(), 'Organisasi contoh tidak boleh ada di produksi.');
        $this->assertSame(0, $this->jumlahAdminContoh(), 'Akun Super Admin contoh tidak boleh ada di produksi.');
    }

    public function test_database_seeder_di_luar_produksi_tetap_menyemai_data_contoh(): void
    {
        $this->assertFalse($this->app->environment('production'));

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, $this->jumlahOrganisasiContoh());
        $this->assertSame(1, $this->jumlahAdminContoh());
    }

    /** Kata sandi contoh dibaca dari konfigurasi, bukan tertanam di source. */
    public function test_kata_sandi_admin_contoh_mengikuti_konfigurasi(): void
    {
        config(['amanpoll.demo.kata_sandi' => 'kata-sandi-khusus-uji']);

        $this->seed(DemoAwalSeeder::class);

        $tersimpan = (string) DB::table('Pengguna')
            ->where('Email', self::EMAIL_ADMIN_CONTOH)
            ->value('KataSandi');

        $this->assertTrue(password_verify('kata-sandi-khusus-uji', $tersimpan));
        $this->assertFalse(
            password_verify('password', $tersimpan),
            'Kata sandi bawaan tidak boleh ikut berlaku saat konfigurasinya diganti.',
        );
    }

    public function test_database_seeder_di_produksi_menyemai_satu_tenant_awal_yang_bisa_dipakai_masuk(): void
    {
        $this->jadikanProduksi();

        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);

        $organisasiId = DB::table('Organisasi')->where('Kode', 'AWAL')->value('Id');
        $this->assertNotNull($organisasiId, 'Tenant awal harus ada di produksi.');
        $this->assertSame(1, DB::table('Organisasi')->count());

        $admin = DB::table('Pengguna')->where('Email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame($organisasiId, $admin->OrganisasiId);
        $this->assertTrue(password_verify('password1234', (string) $admin->KataSandi));

        $peranPemilik = DB::table('Peran')->where('OrganisasiId', $organisasiId)->where('Kode', 'PEMILIK')->value('Id');
        $this->assertSame(
            1,
            DB::table('PenggunaPeran')->where('PenggunaId', $admin->Id)->where('PeranId', $peranPemilik)->count(),
        );
        $this->assertSame(DB::table('Izin')->count(), DB::table('PeranIzin')->where('PeranId', $peranPemilik)->count());
        $this->assertGreaterThan(1, DB::table('Peran')->where('OrganisasiId', $organisasiId)->count(), 'Peran bawaan ikut terpasang.');
    }

    public function test_tenant_awal_tidak_dobel_dan_tidak_mengembalikan_kata_sandi_yang_sudah_diganti(): void
    {
        $this->seed(IzinSeeder::class);
        $this->seed(TenantAwalSeeder::class);

        DB::table('Pengguna')->where('Email', 'admin@gmail.com')->update(['KataSandi' => password_hash('sudah-diganti', PASSWORD_BCRYPT)]);

        $this->seed(TenantAwalSeeder::class);

        $this->assertSame(1, DB::table('Organisasi')->where('Kode', 'AWAL')->count());
        $this->assertSame(1, DB::table('Pengguna')->where('Email', 'admin@gmail.com')->count());
        $this->assertSame(1, DB::table('Peran')->where('OrganisasiId', DB::table('Organisasi')->where('Kode', 'AWAL')->value('Id'))->where('Kode', 'PEMILIK')->count());
        $this->assertTrue(password_verify('sudah-diganti', (string) DB::table('Pengguna')->where('Email', 'admin@gmail.com')->value('KataSandi')));
    }

    public function test_tenant_awal_mengikuti_konfigurasi(): void
    {
        config(['amanpoll.tenant_awal.email' => 'pemilik@contoh.test', 'amanpoll.tenant_awal.kata_sandi' => 'kata-sandi-khusus-uji']);

        $this->seed(TenantAwalSeeder::class);

        $tersimpan = (string) DB::table('Pengguna')->where('Email', 'pemilik@contoh.test')->value('KataSandi');
        $this->assertTrue(password_verify('kata-sandi-khusus-uji', $tersimpan));
        $this->assertSame(0, DB::table('Pengguna')->where('Email', 'admin@gmail.com')->count());
    }
}
