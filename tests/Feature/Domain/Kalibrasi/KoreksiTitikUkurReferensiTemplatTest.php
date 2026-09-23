<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Kalibrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Application\Actions\KelolaPelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Koreksi titik ukur (NilaiTerukur - NilaiReferensi) saat referensi diambil dari templat.
 *
 * `SimpanHasilTitikUkurKalibrasiRequest` membolehkan `NilaiReferensi` kosong,
 * dan `KelolaPelaksanaanKalibrasi::simpanHasilTitikUkur()` memang mengisinya
 * dari TitikUkurKalibrasi terhubung lalu memakainya untuk menilai lolos/gagal.
 * Koreksi seharusnya ikut dihitung dari referensi yang sama.
 */
final class KoreksiTitikUkurReferensiTemplatTest extends TestCase
{
    use RefreshDatabase;

    /** Pembanding: referensi dikirim, koreksi terhitung. */
    public function test_koreksi_terhitung_saat_referensi_dikirim(): void
    {
        [$pengguna, $hasil] = $this->siapkanPelaksanaan();

        $this->simpan($pengguna, $hasil, ['NilaiReferensi' => 100]);

        $segar = $hasil->fresh();
        $this->assertSame('Gagal', $segar?->Hasil);
        $this->assertSame(4.0, (float) $segar?->Koreksi);
    }

    /**
     * BUG: koreksi dihitung sebelum referensi templat diambil, sehingga
     * tersimpan NULL padahal NilaiReferensi dan NilaiTerukur pada baris yang
     * sama sama-sama terisi dan hasil lolos/gagalnya sudah dinilai.
     */
    public function test_koreksi_terhitung_saat_referensi_diambil_dari_templat(): void
    {
        [$pengguna, $hasil] = $this->siapkanPelaksanaan();

        $this->simpan($pengguna, $hasil, []);

        $segar = $hasil->fresh();
        $this->assertSame(100.0, (float) $segar?->NilaiReferensi, 'Referensi diisi dari templat.');
        $this->assertSame(104.0, (float) $segar?->NilaiTerukur);
        $this->assertSame('Gagal', $segar?->Hasil, 'Lolos/gagal dinilai memakai referensi dan toleransi templat.');
        $this->assertNotNull($segar?->Koreksi, 'Koreksi tersimpan NULL walau referensi dan nilai terukur diketahui.');
        $this->assertSame(4.0, (float) $segar->Koreksi);
    }

    /** @param array<string, mixed> $tambahan */
    private function simpan(Pengguna $pengguna, HasilTitikUkurKalibrasi $hasil, array $tambahan): void
    {
        $this->actingAs($pengguna)->put("/kalibrasi/pelaksanaan/{$hasil->PelaksanaanKalibrasiId}/hasil-titik-ukur", [
            'hasil' => [[
                'Id' => $hasil->Id,
                'TitikUkurKalibrasiId' => $hasil->TitikUkurKalibrasiId,
                'NamaTitik' => $hasil->NamaTitik,
                'NilaiTerukur' => 104,
                ...$tambahan,
            ]],
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        app(KonteksOrganisasi::class)->tetapkan($pengguna->OrganisasiId);
    }

    /** @return array{Pengguna, HasilTitikUkurKalibrasi} */
    private function siapkanPelaksanaan(): array
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-KAL-KOREKSI', 'Nama' => 'Organisasi Koreksi Kalibrasi']);
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Manajer Kalibrasi',
            'Email' => 'manajer.koreksi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $peran = Peran::create(['Kode' => 'PERAN-KAL', 'Nama' => 'Pengelola Kalibrasi']);
        $izin = Izin::firstOrCreate(['Kode' => 'Kalibrasi.Kelola'], ['Nama' => 'Kalibrasi.Kelola', 'Modul' => 'Kalibrasi']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        $kategori = KategoriAset::create(['Kode' => 'KAT-ALKES', 'Nama' => 'Alat Kesehatan']);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-TENSI-01',
            'Nama' => 'Tensimeter Digital',
            'Status' => StatusAset::Aktif->value,
        ]);
        $jenis = JenisKalibrasi::create(['Kode' => 'JK-TENSI', 'Nama' => 'Kalibrasi Tensimeter', 'Aktif' => true]);
        TitikUkurKalibrasi::create([
            'JenisKalibrasiId' => $jenis->Id,
            'Nama' => 'Titik 100 mmHg',
            'Satuan' => 'mmHg',
            'NilaiReferensi' => 100,
            'ToleransiMinus' => 3,
            'ToleransiPlus' => 3,
            'Urutan' => 1,
            'Aktif' => true,
        ]);

        /** @var PelaksanaanKalibrasi $pelaksanaan */
        $pelaksanaan = app(KelolaPelaksanaanKalibrasi::class)->jadwalkan([
            'AsetId' => $aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'TanggalKalibrasi' => '2026-09-21',
        ], $pengguna->Id);

        return [$pengguna, HasilTitikUkurKalibrasi::query()->where('PelaksanaanKalibrasiId', $pelaksanaan->Id)->sole()];
    }
}
