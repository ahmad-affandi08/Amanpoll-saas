<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Core\Penomoran\Services\LayananKodeOtomatis;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kode data induk dibuat sendiri oleh server supaya tidak perlu diketik.
 *
 * Yang dijaga di sini bukan bentuk kodenya, melainkan janji yang membuatnya
 * aman dipakai: tidak pernah kembar, tidak menimpa kode yang ditulis pengguna,
 * dan tidak bertabrakan dengan kode organisasi lain.
 */
final class KodeOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-KODE', 'Nama' => 'Organisasi Kode']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    public function test_kode_terisi_sendiri_dan_berurut_saat_dikosongkan(): void
    {
        $pertama = Gudang::create(['Nama' => 'Gudang Pusat', 'Status' => 'Aktif']);
        $kedua = Gudang::create(['Nama' => 'Gudang Cabang', 'Status' => 'Aktif']);

        $this->assertSame('GDG-0001', $pertama->Kode);
        $this->assertSame('GDG-0002', $kedua->Kode);
    }

    public function test_kode_yang_diisi_pengguna_tidak_ditimpa(): void
    {
        $gudang = Gudang::create(['Kode' => 'GUDANG-SAYA', 'Nama' => 'Gudang Pusat', 'Status' => 'Aktif']);

        $this->assertSame('GUDANG-SAYA', $gudang->Kode);
        $this->assertDatabaseCount('UrutanKode', 0);
    }

    /** Kode manual bisa menempati nomor yang belum dilewati penghitung. */
    public function test_kode_otomatis_melompati_nomor_yang_sudah_dipakai_manual(): void
    {
        Gudang::create(['Kode' => 'GDG-0001', 'Nama' => 'Gudang Manual', 'Status' => 'Aktif']);

        $otomatis = Gudang::create(['Nama' => 'Gudang Otomatis', 'Status' => 'Aktif']);

        $this->assertSame('GDG-0002', $otomatis->Kode);
    }

    public function test_penghitung_terpisah_antar_organisasi(): void
    {
        Gudang::create(['Nama' => 'Gudang A', 'Status' => 'Aktif']);

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'Organisasi Lain']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);

        $gudangLain = Gudang::create(['Nama' => 'Gudang B', 'Status' => 'Aktif']);

        // Dua organisasi sama-sama mulai dari 1; keunikannya dibatasi per tenant.
        $this->assertSame('GDG-0001', $gudangLain->Kode);
        $this->assertDatabaseCount('UrutanKode', 2);
    }

    public function test_penghitung_dibuat_sendiri_tanpa_penyiapan_lebih_dulu(): void
    {
        $this->assertDatabaseCount('UrutanKode', 0);

        Gudang::create(['Nama' => 'Gudang Pusat', 'Status' => 'Aktif']);

        $this->assertDatabaseHas('UrutanKode', [
            'OrganisasiId' => $this->organisasi->Id,
            'Entitas' => 'Gudang',
            'Terakhir' => 1,
        ]);
    }

    /** Entitas platform tidak bertenant, jadi lingkupnya memakai penanda tetap. */
    public function test_lingkup_platform_memakai_penanda_bukan_null(): void
    {
        app(LayananKodeOtomatis::class)->berikutnya(
            entitas: 'ContohPlatform',
            awalan: 'CTH',
            organisasiId: null,
            sudahDipakai: static fn (): bool => false,
        );

        $this->assertDatabaseHas('UrutanKode', [
            'OrganisasiId' => LayananKodeOtomatis::PLATFORM,
            'Entitas' => 'ContohPlatform',
        ]);
        $this->assertSame(
            0,
            DB::table('UrutanKode')->whereNull('OrganisasiId')->count(),
            'Lingkup platform tidak boleh disimpan sebagai NULL.',
        );
    }

    public function test_menyerah_dengan_pesan_jelas_bila_seluruh_nomor_terpakai(): void
    {
        $this->expectExceptionMessageMatches('/belum terpakai setelah/');

        app(LayananKodeOtomatis::class)->berikutnya(
            entitas: 'Gudang',
            awalan: 'GDG',
            organisasiId: $this->organisasi->Id,
            sudahDipakai: static fn (): bool => true,
        );
    }

    /** Kode yang sudah terbit tidak boleh hilang saat form dikirim tanpa kolom kode. */
    public function test_menyunting_tanpa_mengirim_kode_tidak_mengosongkannya(): void
    {
        $gudang = Gudang::create(['Nama' => 'Gudang Pusat', 'Status' => 'Aktif']);

        $gudang->update(['Kode' => '', 'Nama' => 'Gudang Pusat Baru']);

        $this->assertSame('GDG-0001', $gudang->fresh()->Kode);
    }

    public function test_membuat_lewat_http_tanpa_kode_diterima_dan_terisi_sendiri(): void
    {
        $pengguna = $this->penggunaDenganIzin(['Stok.Kelola']);

        $this->actingAs($pengguna)
            ->post('/gudang', ['Nama' => 'Gudang Pusat', 'Status' => 'Aktif'])
            ->assertSessionDoesntHaveErrors();

        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->assertSame('GDG-0001', Gudang::query()->firstOrFail()->Kode);
    }

    /** Kode yang menjadi kunci di kode program tetap harus diketik. */
    public function test_entitas_yang_dikecualikan_tetap_mewajibkan_kode(): void
    {
        foreach (['IntegrasiEksternal', 'TemplatNotifikasi', 'StandarKepatuhan'] as $berkas) {
            $aturan = file_get_contents(base_path($this->berkasAturan($berkas)));
            $wajib = str_contains((string) $aturan, "'Kode' => [\n                'required'")
                || str_contains((string) $aturan, "'Kode' => ['required'");

            if ($berkas === 'StandarKepatuhan') {
                $this->assertFalse($wajib, 'StandarKepatuhan seharusnya sudah memakai kode otomatis.');

                continue;
            }

            $this->assertTrue($wajib, "{$berkas} tidak boleh ikut kode otomatis: kodenya dirujuk kode program.");
        }
    }

    private function berkasAturan(string $nama): string
    {
        return match ($nama) {
            'IntegrasiEksternal' => 'app/Domain/Kepatuhan/Http/Requests/SimpanIntegrasiEksternalRequest.php',
            'TemplatNotifikasi' => 'app/Domain/Notifikasi/Http/Requests/SimpanTemplatNotifikasiRequest.php',
            'StandarKepatuhan' => 'app/Domain/Kepatuhan/Http/Requests/SimpanStandarKepatuhanRequest.php',
        };
    }

    /** @param list<string> $kodeIzin */
    private function penggunaDenganIzin(array $kodeIzin): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Pengguna Kode',
            'Email' => 'kode@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['Kode' => 'PERAN-KODE', 'Nama' => 'Peran Kode']);

        foreach ($kodeIzin as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
