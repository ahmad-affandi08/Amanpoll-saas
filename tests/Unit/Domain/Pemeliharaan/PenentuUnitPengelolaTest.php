<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Application\Services\PenentuUnitPengelola;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Penurunan unit pengelola tiket (PRD 8.21): keluhan dari kategori (naik ke
 * induk) lalu aset; perintah kerja dari isian, keluhan, aset, lalu rencana.
 */
final class PenentuUnitPengelolaTest extends TestCase
{
    use RefreshDatabase;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    private UnitOrganisasi $icu;

    protected function setUp(): void
    {
        parent::setUp();

        $organisasi = Organisasi::create(['Kode' => 'ORG-PUP', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        $this->it = $this->buatUnit('IT', 'Instalasi IT', true);
        $this->ipsrs = $this->buatUnit('IPS', 'IPSRS', true);
        $this->icu = $this->buatUnit('ICU', 'ICU', false);
    }

    public function test_keluhan_memakai_unit_pengelola_kategori_lebih_dulu(): void
    {
        $kategori = $this->buatKategori('Jaringan', $this->it);
        $aset = $this->buatAset($this->ipsrs);

        $this->assertSame($this->it->Id, $this->penentu()->untukKeluhan($kategori->Id, $aset->Id));
    }

    public function test_keluhan_naik_ke_induk_kategori_sampai_ketemu(): void
    {
        $kakek = $this->buatKategori('TI', $this->it);
        $induk = $this->buatKategori('Perangkat', null, $kakek);
        $kategori = $this->buatKategori('Printer', null, $induk);

        $this->assertSame($this->it->Id, $this->penentu()->untukKeluhan($kategori->Id, null));
    }

    public function test_keluhan_jatuh_ke_aset_bila_kategori_tanpa_unit_pengelola(): void
    {
        $kategori = $this->buatKategori('Umum', null);
        $aset = $this->buatAset($this->ipsrs);

        $this->assertSame($this->ipsrs->Id, $this->penentu()->untukKeluhan($kategori->Id, $aset->Id));
    }

    public function test_keluhan_tanpa_kategori_dan_aset_berunit_pengelola_kosong(): void
    {
        $this->assertNull($this->penentu()->untukKeluhan(null, null));
        $this->assertNull($this->penentu()->untukKeluhan($this->buatKategori('Umum', null)->Id, $this->buatAset(null)->Id));
    }

    /** Hierarki induk yang rusak tidak boleh membuat penurunan berputar selamanya. */
    public function test_siklus_induk_kategori_berhenti_lalu_jatuh_ke_aset(): void
    {
        $satu = $this->buatKategori('Satu', null);
        $dua = $this->buatKategori('Dua', null, $satu);
        DB::table('KategoriKeluhan')->where('Id', $satu->Id)->update(['IndukId' => $dua->Id]);

        $this->assertSame($this->ipsrs->Id, $this->penentu()->untukKeluhan($satu->Id, $this->buatAset($this->ipsrs)->Id));
        $this->assertNull($this->penentu()->untukKeluhan($dua->Id, null));
    }

    /**
     * Hasil tidak boleh bergantung pada siapa yang membuat tiket: pelapor
     * berlingkup ruangan lain tetap mendapat unit pengelola aset IT.
     */
    public function test_aset_di_luar_lingkup_pembuat_tetap_terbaca(): void
    {
        $aset = $this->buatAset($this->it);
        $ruangLain = Lokasi::create(['Kode' => 'R9', 'Nama' => 'Poli Gigi']);

        $pelapor = Pengguna::create([
            'OrganisasiId' => app(KonteksOrganisasi::class)->id(),
            'Nama' => 'Pelapor',
            'Email' => 'pelapor+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        PenggunaPeran::create([
            'PenggunaId' => $pelapor->Id,
            'PeranId' => Peran::create(['Kode' => 'PLP', 'Nama' => 'Pelapor'])->Id,
            'LokasiId' => $ruangLain->Id,
        ]);
        $this->actingAs($pelapor, 'web');

        $this->assertFalse(Aset::query()->whereKey($aset->Id)->exists(), 'Prasyarat: aset di luar lingkup pelapor.');
        $this->assertSame($this->it->Id, $this->penentu()->untukKeluhan(null, $aset->Id));
        $this->assertSame($this->icu->Id, $this->penentu()->unitOrganisasiDariAset(null, $aset->Id));
    }

    public function test_perintah_kerja_isian_eksplisit_menang(): void
    {
        $keluhan = $this->buatKeluhan($this->it, null);

        $this->assertSame(
            $this->ipsrs->Id,
            $this->penentu()->untukPerintahKerja($this->ipsrs->Id, $keluhan, $this->buatAset($this->it)->Id, $this->it->Id),
        );
    }

    public function test_perintah_kerja_mengikuti_keluhan_asal(): void
    {
        $keluhan = $this->buatKeluhan($this->it, $this->buatAset($this->ipsrs));

        $this->assertSame($this->it->Id, $this->penentu()->untukPerintahKerja(null, $keluhan, $this->buatAset($this->ipsrs)->Id, $this->ipsrs->Id));
    }

    public function test_perintah_kerja_jatuh_ke_aset_yang_diberikan(): void
    {
        $keluhan = $this->buatKeluhan(null, null);

        $this->assertSame($this->ipsrs->Id, $this->penentu()->untukPerintahKerja('', $keluhan, $this->buatAset($this->ipsrs)->Id, $this->it->Id));
    }

    /** Keluhan lama tanpa unit pengelola: aset keluhannya yang dipakai bila aset tidak diberikan. */
    public function test_perintah_kerja_jatuh_ke_aset_keluhan_bila_aset_tidak_diberikan(): void
    {
        $keluhan = $this->buatKeluhan(null, $this->buatAset($this->ipsrs));

        $this->assertSame($this->ipsrs->Id, $this->penentu()->untukPerintahKerja(null, $keluhan, null, $this->it->Id));
    }

    public function test_perintah_kerja_jatuh_ke_rencana_lalu_kosong(): void
    {
        $asetTanpaPengelola = $this->buatAset(null);

        $this->assertSame($this->it->Id, $this->penentu()->untukPerintahKerja(null, null, $asetTanpaPengelola->Id, $this->it->Id));
        $this->assertNull($this->penentu()->untukPerintahKerja(null, null, $asetTanpaPengelola->Id, null));
        $this->assertNull($this->penentu()->untukPerintahKerja(null, null, null, ''));
    }

    public function test_unit_organisasi_perintah_kerja_diisi_dari_aset_bila_kosong(): void
    {
        $aset = $this->buatAset($this->it);

        $this->assertSame($this->ipsrs->Id, $this->penentu()->unitOrganisasiDariAset($this->ipsrs->Id, $aset->Id));
        $this->assertSame($this->icu->Id, $this->penentu()->unitOrganisasiDariAset(null, $aset->Id));
        $this->assertNull($this->penentu()->unitOrganisasiDariAset(null, null));
    }

    private function penentu(): PenentuUnitPengelola
    {
        return app(PenentuUnitPengelola::class);
    }

    private function buatUnit(string $kode, string $nama, bool $mengelolaAset): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'Kode' => $kode,
            'Nama' => $nama,
            'Jenis' => 'Instalasi',
            'Status' => 'Aktif',
            'MengelolaAset' => $mengelolaAset,
        ]);
    }

    private function buatKategori(string $nama, ?UnitOrganisasi $pengelola, ?KategoriKeluhan $induk = null): KategoriKeluhan
    {
        return KategoriKeluhan::create([
            'Nama' => $nama,
            'IndukId' => $induk?->Id,
            'UnitPengelolaId' => $pengelola?->Id,
        ]);
    }

    private function buatAset(?UnitOrganisasi $pengelola): Aset
    {
        return Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Alat '.uniqid()])->Id,
            'UnitOrganisasiId' => $this->icu->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Nama' => 'Aset Uji',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    private function buatKeluhan(?UnitOrganisasi $pengelola, ?Aset $aset): Keluhan
    {
        return Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'AsetId' => $aset?->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Judul' => 'Rusak',
            'Deskripsi' => 'Uji',
        ]);
    }
}
