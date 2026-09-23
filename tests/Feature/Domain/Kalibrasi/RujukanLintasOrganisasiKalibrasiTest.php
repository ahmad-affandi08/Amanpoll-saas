<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Kalibrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Rujukan pada formulir kalibrasi dibatasi pada organisasi pemanggil.
 *
 * Sebelumnya ID penyedia, perintah kerja, pelaksana, dan jenis kalibrasi
 * hanya diperiksa sebagai string. ID milik organisasi lain lolos lalu
 * tersimpan, sehingga kalibrasi rumah sakit A menunjuk penyedia atau
 * pelaksana milik rumah sakit B. Tiap kasus di sini membawa pembanding: nilai
 * milik organisasi sendiri tetap diterima, supaya penolakan yang diuji memang
 * karena organisasinya, bukan karena formulirnya rusak.
 */
final class RujukanLintasOrganisasiKalibrasiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $milikSendiri = [];

    /** @var array<string, string> */
    private array $milikLain = [];

    private Pengguna $manajer;

    private Organisasi $organisasi;

    protected function setUp(): void
    {
        parent::setUp();

        $lain = Organisasi::create(['Kode' => 'ORG-RUJ-B', 'Nama' => 'Rumah Sakit Lain']);
        $this->milikLain = $this->semaiRujukan($lain, 'B');

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-RUJ-A', 'Nama' => 'Rumah Sakit Sendiri']);
        $this->milikSendiri = $this->semaiRujukan($this->organisasi, 'A');
        $this->manajer = $this->buatManajer($this->organisasi);
    }

    /** @return array<string, array{0: string}> */
    public static function rujukanPelaksanaan(): array
    {
        return [
            'jenis kalibrasi' => ['JenisKalibrasiId'],
            'penyedia' => ['PenyediaId'],
            'perintah kerja' => ['PerintahKerjaId'],
            'pelaksana' => ['DilaksanakanOleh'],
        ];
    }

    #[DataProvider('rujukanPelaksanaan')]
    public function test_pelaksanaan_menolak_rujukan_milik_organisasi_lain(string $kolom): void
    {
        $formulir = $this->formulirPelaksanaan();

        $this->actingAs($this->manajer)
            ->post('/kalibrasi/pelaksanaan', [...$formulir, $kolom => $this->milikLain[$kolom]])
            ->assertSessionHasErrors($kolom);
        $this->assertSame(0, $this->jumlah(PelaksanaanKalibrasi::class), 'Tidak ada pelaksanaan yang boleh tertulis.');

        // Pembanding: formulir yang sama dengan rujukan milik sendiri diterima.
        $this->actingAs($this->manajer)
            ->post('/kalibrasi/pelaksanaan', $formulir)
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $this->jumlah(PelaksanaanKalibrasi::class));
    }

    /** @return array<string, array{0: string}> */
    public static function rujukanRencana(): array
    {
        return [
            'aset' => ['AsetId'],
            'jenis kalibrasi' => ['JenisKalibrasiId'],
            'penyedia' => ['PenyediaId'],
        ];
    }

    #[DataProvider('rujukanRencana')]
    public function test_rencana_menolak_rujukan_milik_organisasi_lain(string $kolom): void
    {
        $formulir = [
            'AsetId' => $this->milikSendiri['AsetId'],
            'JenisKalibrasiId' => $this->milikSendiri['JenisKalibrasiId'],
            'PenyediaId' => $this->milikSendiri['PenyediaId'],
            'IntervalHari' => 365,
            'TanggalMulai' => '2026-01-01',
        ];

        $this->actingAs($this->manajer)
            ->post('/kalibrasi/rencana', [...$formulir, $kolom => $this->milikLain[$kolom]])
            ->assertSessionHasErrors($kolom);
        $this->assertSame(0, $this->jumlah(RencanaKalibrasi::class), 'Tidak ada rencana yang boleh tertulis.');

        $this->actingAs($this->manajer)->post('/kalibrasi/rencana', $formulir)->assertSessionHasNoErrors();
        $this->assertSame(1, $this->jumlah(RencanaKalibrasi::class));
    }

    public function test_titik_ukur_menolak_kategori_aset_milik_organisasi_lain(): void
    {
        $jenis = $this->milikSendiri['JenisKalibrasiId'];
        $sebelum = $this->jumlah(TitikUkurKalibrasi::class);
        $formulir = ['Nama' => 'Titik 100', 'Satuan' => 'mmHg', 'NilaiReferensi' => 100, 'Urutan' => 1];

        $this->actingAs($this->manajer)
            ->post("/kalibrasi/jenis/{$jenis}/titik-ukur", [...$formulir, 'KategoriAsetId' => $this->milikLain['KategoriAsetId']])
            ->assertSessionHasErrors('KategoriAsetId');
        $this->assertSame($sebelum, $this->jumlah(TitikUkurKalibrasi::class));

        $this->actingAs($this->manajer)
            ->post("/kalibrasi/jenis/{$jenis}/titik-ukur", [...$formulir, 'KategoriAsetId' => $this->milikSendiri['KategoriAsetId']])
            ->assertSessionHasNoErrors();
        $this->assertSame($sebelum + 1, $this->jumlah(TitikUkurKalibrasi::class));
    }

    /** @return array<string, mixed> */
    private function formulirPelaksanaan(): array
    {
        return [
            'AsetId' => $this->milikSendiri['AsetId'],
            'JenisKalibrasiId' => $this->milikSendiri['JenisKalibrasiId'],
            'PenyediaId' => $this->milikSendiri['PenyediaId'],
            'PerintahKerjaId' => $this->milikSendiri['PerintahKerjaId'],
            'DilaksanakanOleh' => $this->milikSendiri['DilaksanakanOleh'],
            'TanggalKalibrasi' => '2026-09-21',
        ];
    }

    /** @param  class-string<Model>  $model */
    private function jumlah(string $model): int
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        return $model::query()->count();
    }

    /**
     * Satu set rujukan lengkap untuk satu organisasi.
     *
     * @return array<string, string>
     */
    private function semaiRujukan(Organisasi $organisasi, string $akhiran): array
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        NomorDokumen::create([
            'JenisDokumen' => 'Kalibrasi',
            'Awalan' => 'KAL',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
        $kategori = KategoriAset::create(['Kode' => 'KAT-'.$akhiran, 'Nama' => 'Alat Kesehatan '.$akhiran]);
        $aset = Aset::create([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.$akhiran,
            'Nama' => 'Tensimeter '.$akhiran,
            'Status' => StatusAset::Aktif->value,
        ]);
        $jenis = JenisKalibrasi::create(['Kode' => 'JK-'.$akhiran, 'Nama' => 'Tensimeter '.$akhiran, 'Aktif' => true]);
        $penyedia = Penyedia::create(['Kode' => 'PNY-'.$akhiran, 'Nama' => 'Laboratorium '.$akhiran]);
        $perintahKerja = PerintahKerja::create([
            'Nomor' => 'PK-'.$akhiran,
            'Judul' => 'Kalibrasi berkala',
            'Jenis' => 'Korektif',
            'Status' => 'Draf',
            'Prioritas' => 'Normal',
        ]);
        $pelaksana = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi '.$akhiran,
            'Email' => 'teknisi.'.strtolower($akhiran).'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        return [
            'AsetId' => $aset->Id,
            'KategoriAsetId' => $kategori->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'PenyediaId' => $penyedia->Id,
            'PerintahKerjaId' => $perintahKerja->Id,
            'DilaksanakanOleh' => $pelaksana->Id,
        ];
    }

    private function buatManajer(Organisasi $organisasi): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Manajer Kalibrasi',
            'Email' => 'manajer.kalibrasi@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $peran = Peran::create(['Kode' => 'PERAN-KALIBRASI', 'Nama' => 'Pengelola Kalibrasi']);
        $izin = Izin::firstOrCreate(['Kode' => 'Kalibrasi.Kelola'], ['Nama' => 'Kelola Kalibrasi', 'Modul' => 'Kalibrasi']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);

        return $pengguna;
    }
}
