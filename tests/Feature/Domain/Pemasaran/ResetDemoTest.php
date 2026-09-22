<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananSesiDemo;
use App\Domain\Pemasaran\Application\Services\PengaturUlangDatasetDemo;
use App\Domain\Pemasaran\Application\Services\RegistriDatasetDemo;
use App\Domain\Pemasaran\Domain\Enums\StatusSesiDemo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DemoPemasaran;
use App\Domain\Pemasaran\Infrastructure\Services\DatasetDemoManufaktur;
use App\Domain\Pemasaran\Jobs\ResetDatasetDemo;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** Reset dataset demo tidak pernah menyentuh data di luar tenant demo (Gate 38.03). */
final class ResetDemoTest extends KasusDemo
{
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-06-15 09:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_reset_membangun_ulang_dataset_di_tenant_demo(): void
    {
        $demo = $this->buatDemo();

        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $this->assertSame(5, $this->hitung('Aset', $demo->OrganisasiDemoId));
        $this->assertSame(3, $this->hitung('PerintahKerja', $demo->OrganisasiDemoId));
        $this->assertSame(3, $this->hitung('Lokasi', $demo->OrganisasiDemoId));
        $this->assertSame(1, $this->hitung('RencanaPemeliharaan', $demo->OrganisasiDemoId));
    }

    /** Reset kedua tidak menggandakan isinya; ia menghapus lebih dulu, baru membangun. */
    public function test_reset_berulang_tidak_menggandakan_isinya(): void
    {
        $demo = $this->buatDemo();
        $pengatur = app(PengaturUlangDatasetDemo::class);

        $pengatur->jalankan($demo);
        $pertama = $this->hitung('Aset', $demo->OrganisasiDemoId);
        $pengatur->jalankan($demo);

        $this->assertSame($pertama, $this->hitung('Aset', $demo->OrganisasiDemoId));
    }

    /** Inti Gate 38.03: data tenant sungguhan tidak boleh berkurang satu baris pun. */
    public function test_reset_tidak_menyentuh_data_tenant_sungguhan(): void
    {
        $demo = $this->buatDemo();
        $sungguhan = $this->buatTenantSungguhan();
        $this->isiTenant($sungguhan);

        $sebelum = $this->cuplikan($sungguhan->Id);
        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $this->assertSame($sebelum, $this->cuplikan($sungguhan->Id));
    }

    /** Penjaga kedua: tautan yang salah arah pun tidak cukup, tenantnya harus ikut mengaku tenant demo. */
    public function test_reset_menolak_tenant_yang_tidak_bertanda_demo(): void
    {
        $demo = $this->buatDemo();
        $sungguhan = $this->buatTenantSungguhan();
        $this->isiTenant($sungguhan);

        $demo->OrganisasiDemoId = $sungguhan->Id;
        $demo->save();

        $sebelum = $this->cuplikan($sungguhan->Id);

        try {
            app(PengaturUlangDatasetDemo::class)->jalankan($demo);
            $this->fail('Reset ke tenant tanpa tanda demo seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('tidak ditandai sebagai tenant demo', $galat->getMessage());
            $this->assertSame($sebelum, $this->cuplikan($sungguhan->Id));
        }
    }

    public function test_reset_menolak_demo_tanpa_tenant(): void
    {
        $demo = $this->buatDemo(denganTenant: false);

        $this->expectException(AturanBisnisDilanggar::class);

        app(PengaturUlangDatasetDemo::class)->jalankan($demo);
    }

    /** Hanya tabel yang diakui dataset yang boleh disapu, bahkan di dalam tenant demo sendiri. */
    public function test_reset_hanya_menyapu_tabel_yang_diakui_datasetnya(): void
    {
        $demo = $this->buatDemo();
        $tenant = Organisasi::query()->whereKey($demo->OrganisasiDemoId)->firstOrFail();

        $penyediaId = (string) Str::ulid();
        DB::table('Penyedia')->insert([
            'Id' => $penyediaId,
            'OrganisasiId' => $tenant->Id,
            'Kode' => 'VND-DEMO',
            'Nama' => 'Vendor Demo',
            'Status' => 'Aktif',
            'DibuatPada' => now(),
            'DiperbaruiPada' => now(),
        ]);

        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $this->assertSame(1, DB::table('Penyedia')->where('Id', $penyediaId)->count());
        $this->assertNotContains('Penyedia', app(DatasetDemoManufaktur::class)->tabel());
    }

    /** Sesi yang sedang berjalan menunjuk data yang sebentar lagi lenyap, jadi ditutup lebih dulu. */
    public function test_reset_menutup_sesi_yang_sedang_berjalan(): void
    {
        $demo = $this->buatDemo();
        $sesi = app(LayananSesiDemo::class)->mulai($demo, $this->pengunjung());

        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $this->assertSame(StatusSesiDemo::Dihentikan, $sesi->fresh()?->Status);
    }

    public function test_job_melewati_demo_yang_intervalnya_belum_lewat(): void
    {
        $demo = $this->buatDemo();
        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $pertama = $demo->fresh()?->TerakhirResetPada;
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(30));
        (new ResetDatasetDemo)->handle(app(PengaturUlangDatasetDemo::class), app(LayananSesiDemo::class));

        $this->assertEquals($pertama, $demo->fresh()?->TerakhirResetPada);
    }

    public function test_job_mereset_demo_yang_intervalnya_sudah_lewat(): void
    {
        $demo = $this->buatDemo();
        app(PengaturUlangDatasetDemo::class)->jalankan($demo);

        $pertama = $demo->fresh()?->TerakhirResetPada;
        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(61));
        (new ResetDatasetDemo)->handle(app(PengaturUlangDatasetDemo::class), app(LayananSesiDemo::class));

        $this->assertNotEquals($pertama, $demo->fresh()?->TerakhirResetPada);
    }

    /** Satu demo yang setelannya salah tidak boleh menghentikan reset demo lainnya. */
    public function test_job_melanjutkan_demo_lain_walau_satu_setelannya_salah(): void
    {
        $rusak = $this->buatDemo();
        $rusak->OrganisasiDemoId = $this->buatTenantSungguhan()->Id;
        $rusak->save();

        $sehat = DemoPemasaran::create([
            'Kode' => 'produk-kedua',
            'Nama' => 'Demo Kedua',
            'Aktif' => true,
            'Dataset' => DatasetDemoManufaktur::KODE,
            'OrganisasiDemoId' => $this->buatTenantDemo()->Id,
            'ResetIntervalMenit' => 60,
            'ModulTampil' => [],
            'FiturDibatasi' => [],
            'MaksDurasiMenit' => 30,
            'MaksSesiSerentak' => 5,
        ]);

        (new ResetDatasetDemo)->handle(app(PengaturUlangDatasetDemo::class), app(LayananSesiDemo::class));

        $this->assertNull($rusak->fresh()?->TerakhirResetPada);
        $this->assertNotNull($sehat->fresh()?->TerakhirResetPada);
    }

    /** Dataset di luar daftar tertutupnya ditolak, bukan dijalankan dengan pembuat karangan. */
    public function test_dataset_di_luar_registri_ditolak(): void
    {
        $demo = $this->buatDemo();
        $demo->Dataset = 'karangan';
        $demo->save();

        $this->expectException(AturanBisnisDilanggar::class);

        app(PengaturUlangDatasetDemo::class)->jalankan($demo);
    }

    public function test_tiap_dataset_terdaftar_menyebut_tabel_yang_benar_benar_ada(): void
    {
        $registri = app(RegistriDatasetDemo::class);

        $this->assertNotEmpty($registri->kode());

        foreach ($registri->kode() as $kode) {
            foreach ($registri->ambil($kode)->tabel() as $tabel) {
                $this->assertTrue(
                    Schema::hasTable($tabel),
                    "Dataset {$kode} menyebut tabel {$tabel} yang tidak ada.",
                );
            }
        }
    }

    /** Tiap tabel yang disapu harus punya kolom OrganisasiId, kalau tidak sapuannya melewati batas tenant. */
    public function test_tiap_tabel_dataset_bertenant(): void
    {
        $registri = app(RegistriDatasetDemo::class);

        foreach ($registri->kode() as $kode) {
            foreach ($registri->ambil($kode)->tabel() as $tabel) {
                $this->assertTrue(
                    Schema::hasColumn($tabel, 'OrganisasiId'),
                    "Tabel {$tabel} tidak punya OrganisasiId; reset akan menyapu lintas tenant.",
                );
            }
        }
    }

    private function isiTenant(Organisasi $organisasi): void
    {
        app(DatasetDemoManufaktur::class)->bangun($organisasi->Id);
    }

    /** @return array<string, int> */
    private function cuplikan(string $organisasiId): array
    {
        $hasil = [];

        foreach (app(DatasetDemoManufaktur::class)->tabel() as $tabel) {
            $hasil[$tabel] = $this->hitung($tabel, $organisasiId);
        }

        return $hasil;
    }

    private function hitung(string $tabel, ?string $organisasiId): int
    {
        return DB::table($tabel)->where('OrganisasiId', $organisasiId)->count();
    }
}
