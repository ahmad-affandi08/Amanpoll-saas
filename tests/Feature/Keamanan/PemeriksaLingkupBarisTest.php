<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Core\Izin\PemeriksaLingkupBaris;
use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
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
 * "Apakah lingkup pengguna ini mencakup baris ini" (PRD 8.21), dipakai
 * penugasan teknisi dan penerima notifikasi. Jawabannya harus sama persis
 * dengan ScopeLingkup; kalau tidak, server menugaskan tiket kepada orang yang
 * lalu tidak dapat membukanya.
 */
class PemeriksaLingkupBarisTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Peran $peran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-PLB', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
        $this->peran = Peran::create(['Kode' => 'STAF', 'Nama' => 'Staf']);
    }

    public function test_penugasan_tanpa_cakupan_mencakup_semua_baris(): void
    {
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'IGD']));
        $pengguna = $this->buatPengguna();

        $this->assertTrue($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
    }

    /** Meniru LingkupAkses: tanpa peran berarti tanpa batas; penyaringan izin tugas pemanggil. */
    public function test_pengguna_tanpa_peran_dianggap_tanpa_batas(): void
    {
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'IGD']));
        $pengguna = $this->buatPengguna(tanpaPeran: true);

        $this->assertTrue($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
    }

    public function test_unit_turunan_ikut_tercakup(): void
    {
        $induk = $this->buatUnit('U1', 'Penunjang');
        $anak = $this->buatUnit('U1A', 'Laboratorium', $induk);
        $aset = $this->buatAset(unit: $anak);

        $pengguna = $this->buatPengguna(unit: $induk);

        $this->assertTrue($this->pemeriksa()->mencakup($pengguna->Id, $aset));
    }

    public function test_lokasi_dalam_lingkup_tercakup(): void
    {
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $keluhan = $this->buatKeluhan($poli);

        $pengguna = $this->buatPengguna(lokasi: $poli);

        $this->assertTrue($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
    }

    /** Pelebaran PRD 8.21: unit pengelola IT mencakup keluhan IT di ruangan mana pun. */
    public function test_unit_pengelola_dalam_lingkup_tercakup_walau_ruangannya_di_luar(): void
    {
        $it = $this->buatUnit('IT', 'Instalasi IT', mengelolaAset: true);
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']), $it);

        $pengguna = $this->buatPengguna(unit: $it);

        $this->assertTrue($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
    }

    public function test_baris_di_luar_lingkup_tidak_tercakup(): void
    {
        $it = $this->buatUnit('IT', 'Instalasi IT', mengelolaAset: true);
        $ipsrs = $this->buatUnit('IPS', 'IPSRS', mengelolaAset: true);
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']), $ipsrs);

        $pengguna = $this->buatPengguna(unit: $it);

        $this->assertFalse($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
    }

    /**
     * Pembanding langsung dengan ScopeLingkup: untuk tiap baris, jawaban
     * pemeriksa sama dengan terlihat-tidaknya baris itu lewat kueri berscope.
     */
    public function test_jawaban_sama_dengan_scope_lingkup(): void
    {
        $it = $this->buatUnit('IT', 'Instalasi IT', mengelolaAset: true);
        $ipsrs = $this->buatUnit('IPS', 'IPSRS', mengelolaAset: true);
        $poli = Lokasi::create(['Kode' => 'R1', 'Nama' => 'Poli Umum']);
        $icu = Lokasi::create(['Kode' => 'R2', 'Nama' => 'ICU']);

        $baris = [
            $this->buatKeluhan($poli),
            $this->buatKeluhan($icu),
            $this->buatKeluhan($icu, $it),
            $this->buatKeluhan($icu, $ipsrs),
            $this->buatKeluhan($poli, $ipsrs),
        ];

        $pengguna = $this->buatPengguna(unit: $it, lokasi: $poli);

        $this->actingAs($pengguna, 'web');
        $terlihat = Keluhan::query()->pluck('Id')->all();
        app('auth')->guard('web')->logout();

        $tercakup = array_values(array_map(
            fn (Keluhan $keluhan): string => $keluhan->Id,
            array_filter($baris, fn (Keluhan $keluhan): bool => $this->pemeriksa()->mencakup($pengguna->Id, $keluhan)),
        ));

        sort($terlihat);
        sort($tercakup);
        $this->assertCount(3, $tercakup);
        $this->assertSame($terlihat, $tercakup);
    }

    /** Eskalasi SLA berjalan dari cron tanpa konteks; lingkup tetap dihitung di organisasi barisnya. */
    public function test_tanpa_konteks_organisasi_lingkup_dihitung_di_organisasi_baris(): void
    {
        $it = $this->buatUnit('IT', 'Instalasi IT', mengelolaAset: true);
        $ipsrs = $this->buatUnit('IPS', 'IPSRS', mengelolaAset: true);
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']), $ipsrs);
        $pengguna = $this->buatPengguna(unit: $it);

        app(KonteksOrganisasi::class)->bersihkan();

        $this->assertFalse($this->pemeriksa()->mencakup($pengguna->Id, $keluhan));
        $this->assertSame([], $this->pemeriksa()->penggunaYangMencakup($keluhan, [$pengguna->Id]));
        $this->assertNull(app(KonteksOrganisasi::class)->id(), 'Konteks asal harus dipulihkan.');
    }

    public function test_menyaring_daftar_pengguna_dengan_urutan_masukan(): void
    {
        $it = $this->buatUnit('IT', 'Instalasi IT', mengelolaAset: true);
        $ipsrs = $this->buatUnit('IPS', 'IPSRS', mengelolaAset: true);
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']), $it);

        $teknisiIt = $this->buatPengguna(unit: $it);
        $teknisiIpsrs = $this->buatPengguna(unit: $ipsrs);
        $koordinator = $this->buatPengguna();

        $hasil = $this->pemeriksa()->penggunaYangMencakup(
            $keluhan,
            collect([$koordinator->Id, $teknisiIpsrs->Id, $teknisiIt->Id, $koordinator->Id]),
        );

        $this->assertSame([$koordinator->Id, $teknisiIt->Id], $hasil);
    }

    /**
     * Organisasi tanpa lingkup: seluruh calon ditetapkan lewat satu kueri
     * PenggunaPeran, bukan satu perhitungan lingkup per pengguna.
     */
    public function test_calon_tanpa_batas_tidak_menimbulkan_kueri_per_pengguna(): void
    {
        $keluhan = $this->buatKeluhan(Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU']));
        $calon = [];

        for ($i = 0; $i < 8; $i++) {
            $calon[] = $this->buatPengguna()->Id;
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $hasil = $this->pemeriksa()->penggunaYangMencakup($keluhan, $calon);
        $jumlahKueri = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($calon, $hasil);
        $this->assertSame(1, $jumlahKueri);
    }

    private function pemeriksa(): PemeriksaLingkupBaris
    {
        return app(PemeriksaLingkupBaris::class);
    }

    private function buatUnit(string $kode, string $nama, ?UnitOrganisasi $induk = null, bool $mengelolaAset = false): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'Kode' => $kode,
            'Nama' => $nama,
            'Jenis' => 'Instalasi',
            'Status' => 'Aktif',
            'IndukId' => $induk?->Id,
            'MengelolaAset' => $mengelolaAset,
        ]);
    }

    private function buatKeluhan(Lokasi $lokasi, ?UnitOrganisasi $unitPengelola = null): Keluhan
    {
        return Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'LokasiId' => $lokasi->Id,
            'UnitPengelolaId' => $unitPengelola?->Id,
            'Judul' => 'Rusak',
            'Deskripsi' => 'Uji',
        ]);
    }

    private function buatAset(?UnitOrganisasi $unit = null): Aset
    {
        return Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Alat '.uniqid()])->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'Nama' => 'Aset Uji',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    private function buatPengguna(?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null, bool $tanpaPeran = false): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Staf',
            'Email' => 'staf+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if (! $tanpaPeran) {
            PenggunaPeran::create([
                'PenggunaId' => $pengguna->Id,
                'PeranId' => $this->peran->Id,
                'UnitOrganisasiId' => $unit?->Id,
                'LokasiId' => $lokasi?->Id,
            ]);
        }

        return $pengguna;
    }
}
