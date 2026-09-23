<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Kesehatan\KesehatanSistemTerganggu;
use App\Core\Kesehatan\PeriksaKesehatanSistem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rute `/up` harus ikut mati ketika ketergantungannya mati.
 *
 * Bawaan Laravel hanya membuktikan PHP hidup dan framework termuat, sehingga
 * ia menjawab 200 di atas basis data yang tidak dapat dihubungi -- persis
 * keadaan yang paling mungkin terjadi sesaat setelah deployment.
 */
class KesehatanSistemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Dengan debug menyala, rute `/up` melempar ulang pengecualiannya alih-alih
        // menjawab 500 -- jadi menguji dengan debug menyala berarti menguji perilaku
        // yang tidak pernah berjalan di produksi.
        config(['app.debug' => false]);
    }

    private ?string $basisDataAsli = null;

    /**
     * Memutus koneksi basis data tanpa menyentuh berkas konfigurasi.
     *
     * Nama aslinya disimpan karena RefreshDatabase memakai koneksi yang sama
     * untuk memutar balik transaksinya saat test selesai; dibiarkan rusak, yang
     * gagal adalah pembersihannya, bukan hal yang sedang diuji.
     */
    private function rusakkanBasisData(): void
    {
        $koneksi = (string) config('database.default');
        $this->basisDataAsli = (string) config("database.connections.{$koneksi}.database");

        config(["database.connections.{$koneksi}.database" => 'basis_data_yang_tidak_ada']);
        DB::purge($koneksi);
    }

    protected function tearDown(): void
    {
        if ($this->basisDataAsli !== null) {
            $koneksi = (string) config('database.default');
            config(["database.connections.{$koneksi}.database" => $this->basisDataAsli]);
            DB::purge($koneksi);
            $this->basisDataAsli = null;
        }

        parent::tearDown();
    }

    public function test_up_menjawab_sehat_saat_seluruh_ketergantungan_hidup(): void
    {
        $this->get('/up')->assertOk();

        $this->getJson('/up')->assertOk()->assertJson(['status' => 'up']);
    }

    public function test_up_menjawab_gagal_saat_basis_data_tidak_dapat_dihubungi(): void
    {
        $this->rusakkanBasisData();

        $this->getJson('/up')
            ->assertStatus(500)
            ->assertJson(['status' => 'down']);
    }

    /** Alasan kegagalan hanya boleh masuk log, tidak ikut ke badan respons. */
    public function test_respons_gagal_tidak_membocorkan_rincian_sistem(): void
    {
        $this->rusakkanBasisData();

        $isi = $this->get('/up')->assertStatus(500)->getContent();

        $this->assertIsString($isi);
        $this->assertStringNotContainsString('basis_data_yang_tidak_ada', $isi);
        $this->assertStringNotContainsString('SQLSTATE', $isi);
    }

    /**
     * Cache yang menerima tulisan tetapi mengembalikan kosong adalah kegagalan
     * yang paling sunyi: tidak ada pengecualian, hanya entitlement dan izin
     * yang diam-diam salah. Karena itu yang diperiksa perjalanan bolak-baliknya,
     * bukan sekadar apakah store-nya dapat dipanggil.
     */
    public function test_cache_yang_tidak_mengembalikan_nilainya_membuat_pemeriksaan_gagal(): void
    {
        Cache::shouldReceive('put')->once()->andReturnTrue();
        Cache::shouldReceive('get')->once()->andReturnNull();
        Cache::shouldReceive('forget')->once()->andReturnTrue();

        try {
            (new PeriksaKesehatanSistem)->handle();
            $this->fail('Cache yang tidak mengembalikan nilainya seharusnya menggagalkan pemeriksaan.');
        } catch (KesehatanSistemTerganggu $e) {
            $this->assertStringContainsString('cache', $e->getMessage());
        }
    }

    public function test_direktori_tulis_yang_hilang_membuat_pemeriksaan_gagal(): void
    {
        $periksa = new PeriksaKesehatanSistem([storage_path('direktori-yang-tidak-pernah-ada')]);

        try {
            $periksa->handle();
            $this->fail('Direktori yang tidak ada seharusnya menggagalkan pemeriksaan.');
        } catch (KesehatanSistemTerganggu $e) {
            $this->assertStringContainsString('direktori tulis', $e->getMessage());
        }
    }

    /**
     * Pembanding bagi test di atas: dengan direktori yang benar-benar ada,
     * pemeriksaan yang sama lolos. Tanpa ini, kegagalan di atas bisa saja
     * berasal dari pemeriksaan lain yang kebetulan ikut rusak.
     */
    public function test_direktori_tulis_yang_ada_lolos_pemeriksaan(): void
    {
        $periksa = new PeriksaKesehatanSistem([storage_path('framework')]);

        $periksa->handle();

        $this->assertDirectoryIsWritable(storage_path('framework'));
    }
}
