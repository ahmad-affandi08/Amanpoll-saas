<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Kalibrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Application\Actions\KelolaPelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Siklus status pelaksanaan kalibrasi (FASE 26.01 "State transition").
 *
 * Kalibrasi tidak memakai enum status: tahapnya dibaca dari `Hasil`
 * ('Terjadwal' lalu hasil akhir) dan `DiverifikasiPada`. Setelah difinalisasi,
 * pelaksanaan menjadi riwayat akreditasi dan tidak boleh lenyap.
 */
final class TransisiPelaksanaanKalibrasiTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 03:00:00', 'UTC'));

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-KAL-TRANS', 'Nama' => 'Organisasi Transisi Kalibrasi']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Petugas Kalibrasi',
            'Email' => 'petugas.kalibrasi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_pelaksanaan_baru_berstatus_terjadwal_dan_belum_diverifikasi(): void
    {
        $pelaksanaan = $this->jadwalkan()->fresh();

        $this->assertSame('Terjadwal', $pelaksanaan?->Hasil);
        $this->assertNull($pelaksanaan?->DiverifikasiPada);
        $this->assertNull($pelaksanaan?->DiverifikasiOleh);
    }

    public function test_finalisasi_memindahkan_pelaksanaan_ke_hasil_akhir_dan_mencatat_verifikator(): void
    {
        $pelaksanaan = $this->jadwalkan();

        $final = $this->aksi()->finalisasi($pelaksanaan, [
            'Hasil' => 'LolosDenganCatatan',
            'NomorSertifikat' => 'SERT-TRANS-001',
            'TanggalKalibrasi' => '2026-09-21',
        ], $this->pengguna->Id);

        $this->assertSame('LolosDenganCatatan', $final->Hasil);
        $this->assertSame('SERT-TRANS-001', $final->NomorSertifikat);
        $this->assertSame($this->pengguna->Id, $final->DiverifikasiOleh);
        $this->assertSame('2026-09-21 10:00:00', $final->DiverifikasiPada?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
    }

    /** Pembanding: tanpa finalisasi, penghapusan memang berjalan dan ikut membuang titik ukurnya. */
    public function test_pelaksanaan_yang_belum_difinalisasi_dapat_dihapus_beserta_titik_ukurnya(): void
    {
        $pelaksanaan = $this->jadwalkan();
        $this->assertSame(1, HasilTitikUkurKalibrasi::query()->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)->count());

        $this->aksi()->hapus($pelaksanaan, $this->pengguna->Id);

        $this->assertFalse(PelaksanaanKalibrasi::query()->whereKey($pelaksanaan->Id)->exists());
        $this->assertSame(0, HasilTitikUkurKalibrasi::query()->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)->count());
    }

    public function test_pelaksanaan_yang_sudah_difinalisasi_tidak_dapat_dihapus(): void
    {
        $pelaksanaan = $this->jadwalkan();
        $final = $this->aksi()->finalisasi($pelaksanaan, [
            'Hasil' => 'Lolos',
            'NomorSertifikat' => 'SERT-TRANS-002',
            'TanggalKalibrasi' => '2026-09-21',
        ], $this->pengguna->Id);

        try {
            $this->aksi()->hapus($final, $this->pengguna->Id);
            $this->fail('Pelaksanaan yang sudah difinalisasi seharusnya ditolak untuk dihapus.');
        } catch (AturanBisnisDilanggar) {
            // diharapkan
        }

        $this->assertTrue(PelaksanaanKalibrasi::query()->whereKey($pelaksanaan->Id)->exists());
        $this->assertSame(1, HasilTitikUkurKalibrasi::query()->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)->count());
    }

    private function aksi(): KelolaPelaksanaanKalibrasi
    {
        return app(KelolaPelaksanaanKalibrasi::class);
    }

    private function jadwalkan(): PelaksanaanKalibrasi
    {
        $kategori = KategoriAset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'KAT-KAL-'.uniqid(),
            'Nama' => 'Alat Ukur',
        ]);
        $aset = Aset::create([
            'OrganisasiId' => $this->organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-KAL-'.uniqid(),
            'Nama' => 'Termometer Referensi',
            'Status' => StatusAset::Aktif->value,
        ]);
        $jenis = JenisKalibrasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Kode' => 'JK-'.uniqid(),
            'Nama' => 'Kalibrasi Suhu',
            'Aktif' => true,
        ]);
        TitikUkurKalibrasi::create([
            'OrganisasiId' => $this->organisasi->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'Nama' => 'Titik 37 °C',
            'Satuan' => '°C',
            'NilaiReferensi' => 37,
            'ToleransiMinus' => 0.2,
            'ToleransiPlus' => 0.2,
            'Urutan' => 1,
            'Aktif' => true,
        ]);

        return $this->aksi()->jadwalkan([
            'AsetId' => $aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'TanggalKalibrasi' => '2026-09-21',
        ], $this->pengguna->Id);
    }
}
