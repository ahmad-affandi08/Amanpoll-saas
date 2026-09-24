<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Penyedia\Domain\Enums\StatusPenyedia;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Kotak cari global di header: hanya modul yang boleh dibuka, hanya data yang boleh dilihat. */
final class PencarianGlobalTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private KategoriAset $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-CARI', 'Nama' => 'RS Cari', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->kategori = KategoriAset::create(['Nama' => 'Alat Medis']);
    }

    public function test_aset_ditemukan_lewat_kode_nama_dan_nomor_seri(): void
    {
        $ruang = Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']);
        $aset = $this->buatAset('Ventilator Hamilton', 'AST-0101', lokasi: $ruang, nomorSeri: 'HM-778');
        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        foreach (['AST-0101', 'hamilton', 'HM-778'] as $kata) {
            $this->cari($pengguna, $kata)
                ->assertOk()
                ->assertJsonPath('kelompok.0.Kelompok', 'Aset')
                ->assertJsonPath('kelompok.0.Hasil.0.Id', $aset->Id)
                ->assertJsonPath('kelompok.0.Hasil.0.Judul', 'Ventilator Hamilton')
                ->assertJsonPath('kelompok.0.Hasil.0.Keterangan', 'AST-0101 · SN HM-778 · ICU')
                ->assertJsonPath('kelompok.0.Hasil.0.Url', '/aset/'.$aset->Id);
        }
    }

    /** Mengetik kode lengkap harus langsung menunjuk barangnya, bukan tenggelam di antara nama yang mirip. */
    public function test_kode_yang_persis_sama_muncul_paling_atas(): void
    {
        $this->buatAset('Alat AST-12 cadangan', 'AST-1200');
        $this->buatAset('Monitor', 'AST-12');
        $pengguna = $this->buatPengguna(['Aset.Lihat']);

        $this->cari($pengguna, 'AST-12')->assertJsonPath('kelompok.0.Hasil.0.Judul', 'Monitor');
    }

    public function test_modul_yang_daftarnya_tidak_boleh_dibuka_tidak_ikut_dicari(): void
    {
        $this->buatAset('Siemens Mobilett', 'AST-0001');
        Penyedia::create(['Kode' => 'PYD-01', 'Nama' => 'PT Siemens Healthineers', 'Status' => StatusPenyedia::Aktif->value]);

        $this->cari($this->buatPengguna(['Aset.Lihat']), 'siemens')
            ->assertJsonCount(1, 'kelompok')
            ->assertJsonPath('kelompok.0.Kelompok', 'Aset');

        $this->cari($this->buatPengguna(['Aset.Lihat', 'Penyedia.Kelola']), 'siemens')
            ->assertJsonPath('kelompok.0.Kelompok', 'Aset')
            ->assertJsonPath('kelompok.1.Kelompok', 'Penyedia')
            ->assertJsonPath('kelompok.1.Hasil.0.Judul', 'PT Siemens Healthineers');
    }

    public function test_data_organisasi_lain_tidak_ikut_ditemukan(): void
    {
        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($lain->Id);
        Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Alat Medis'])->Id,
            'KodeAset' => 'AST-9999',
            'Nama' => 'Ventilator Milik Orang',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->cari($this->buatPengguna(['Aset.Lihat']), 'ventilator')->assertExactJson(['kelompok' => []]);
    }

    public function test_pengguna_berlingkup_unit_hanya_menemukan_aset_unitnya(): void
    {
        $radiologi = UnitOrganisasi::create(['Kode' => 'U1', 'Nama' => 'Radiologi', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $bedah = UnitOrganisasi::create(['Kode' => 'U2', 'Nama' => 'Bedah', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->buatAset('Monitor Radiologi', 'AST-0001', unit: $radiologi);
        $this->buatAset('Monitor Bedah', 'AST-0002', unit: $bedah);

        $this->cari($this->buatPengguna(['Aset.Lihat'], unit: $radiologi), 'monitor')
            ->assertJsonCount(1, 'kelompok.0.Hasil')
            ->assertJsonPath('kelompok.0.Hasil.0.Judul', 'Monitor Radiologi');
    }

    public function test_kata_kurang_dari_dua_huruf_tidak_mencari(): void
    {
        $this->buatAset('Autoclave', 'AST-0001');

        $this->cari($this->buatPengguna(['Aset.Lihat']), 'A')->assertExactJson(['kelompok' => []]);
    }

    /** '%' dan '_' dari pengguna adalah huruf biasa, bukan joker yang mencocokkan seluruh tabel. */
    public function test_joker_like_dari_pengguna_tidak_mencocokkan_semuanya(): void
    {
        $this->buatAset('Autoclave', 'AST-0001');

        $this->cari($this->buatPengguna(['Aset.Lihat']), '%%')->assertExactJson(['kelompok' => []]);
        $this->cari($this->buatPengguna(['Aset.Lihat']), '__')->assertExactJson(['kelompok' => []]);
    }

    public function test_tamu_tidak_dapat_mencari(): void
    {
        $this->getJson('/cari?q=aset')->assertUnauthorized();
    }

    private function cari(Pengguna $pengguna, string $kata): TestResponse
    {
        $respons = $this->actingAs($pengguna)->getJson('/cari?q='.urlencode($kata));

        // Middleware organisasi mengosongkan konteks setelah permintaan selesai.
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        return $respons;
    }

    private function buatAset(
        string $nama,
        string $kode,
        ?UnitOrganisasi $unit = null,
        ?Lokasi $lokasi = null,
        ?string $nomorSeri = null,
    ): Aset {
        return Aset::create([
            'KategoriAsetId' => $this->kategori->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
            'KodeAset' => $kode,
            'Nama' => $nama,
            'NomorSeri' => $nomorSeri,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    /** @param  list<string>  $kodeIzin */
    private function buatPengguna(array $kodeIzin, ?UnitOrganisasi $unit = null): Pengguna
    {
        $peran = Peran::create(['Kode' => 'P-'.uniqid(), 'Nama' => 'Peran Uji']);

        foreach ($kodeIzin as $kode) {
            $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        }

        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Staf',
            'Email' => 'staf+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
        ]);

        return $pengguna;
    }
}
