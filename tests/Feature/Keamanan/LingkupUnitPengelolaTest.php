<?php

declare(strict_types=1);

namespace Tests\Feature\Keamanan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pelebaran lingkup lewat unit pengelola (PRD 8.21).
 *
 * `UnitPengelolaId` masuk peta kolomLingkup Aset, Keluhan, PerintahKerja, dan
 * Gudang bertipe `unit`: pengguna berlingkup unit IT melihat baris yang
 * dipelihara IT di ruangan mana pun. Penambahan itu hanya memperluas; baris
 * yang sebelumnya terlihat lewat ruangan tetap terlihat.
 */
class LingkupUnitPengelolaTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Peran $peran;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    /** Ruangan pemakai (ICU) di luar lingkup unit IT maupun IPSRS. */
    private Lokasi $icu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-LUP', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);

        $this->peran = Peran::create(['Kode' => 'STAF', 'Nama' => 'Staf']);
        $izin = Izin::firstOrCreate(['Kode' => 'Aset.Lihat'], ['Nama' => 'Aset.Lihat', 'Modul' => 'Uji']);
        PeranIzin::create(['PeranId' => $this->peran->Id, 'IzinId' => $izin->Id]);

        $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $icuUnit = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->icu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $icuUnit->Id]);
    }

    public function test_pengguna_berlingkup_unit_it_melihat_baris_kelolaan_it_di_luar_ruangannya(): void
    {
        $this->semaiBarisIcu('IT', $this->it);
        $this->semaiBarisIcu('IPSRS', $this->ipsrs);

        $staf = $this->buatPengguna(unit: $this->it);

        $this->berlaku($staf, function (): void {
            $this->assertSame(['Printer IT'], Aset::query()->pluck('Nama')->all());
            $this->assertSame(['Keluhan IT'], Keluhan::query()->pluck('Judul')->all());
            $this->assertSame(['PK IT'], PerintahKerja::query()->pluck('Judul')->all());
            $this->assertSame(['Gudang IT'], Gudang::query()->pluck('Nama')->all());
        });
    }

    public function test_pengguna_berlingkup_unit_ipsrs_tidak_melihat_baris_kelolaan_it(): void
    {
        $this->semaiBarisIcu('IT', $this->it);
        $this->semaiBarisIcu('IPSRS', $this->ipsrs);

        $staf = $this->buatPengguna(unit: $this->ipsrs);

        $this->berlaku($staf, function (): void {
            $this->assertSame(['Printer IPSRS'], Aset::query()->pluck('Nama')->all());
            $this->assertSame(['Keluhan IPSRS'], Keluhan::query()->pluck('Judul')->all());
            $this->assertSame(['PK IPSRS'], PerintahKerja::query()->pluck('Judul')->all());
            $this->assertSame(['Gudang IPSRS'], Gudang::query()->pluck('Nama')->all());
        });
    }

    /** Hanya memperluas: pengguna berlingkup ruangan tetap melihat seluruh baris di ruangannya. */
    public function test_pengguna_berlingkup_lokasi_tetap_melihat_baris_di_ruangannya(): void
    {
        $this->semaiBarisIcu('IT', $this->it);
        $this->semaiBarisIcu('IPSRS', $this->ipsrs);
        $this->semaiBarisIcu('Tanpa Pengelola', null);

        $perawat = $this->buatPengguna(lokasi: $this->icu);

        $this->berlaku($perawat, function (): void {
            $this->assertSame(3, Aset::query()->count());
            $this->assertSame(3, Keluhan::query()->count());
            $this->assertSame(3, PerintahKerja::query()->count());
            $this->assertSame(3, Gudang::query()->count());
        });
    }

    /** Pelebaran berlaku juga pada route model binding, bukan hanya daftar. */
    public function test_aset_kelolaan_it_dapat_dibuka_lewat_url_oleh_staf_it(): void
    {
        $aset = $this->semaiBarisIcu('IT', $this->it);
        $milikIpsrs = $this->semaiBarisIcu('IPSRS', $this->ipsrs);

        $staf = $this->buatPengguna(unit: $this->it);

        $this->actingAs($staf)->get('/aset/'.$aset->Id)->assertOk();
        $this->actingAs($staf)->get('/aset/'.$milikIpsrs->Id)->assertNotFound();
    }

    /** Menyemai aset, keluhan, perintah kerja, dan gudang di ICU; mengembalikan asetnya. */
    private function semaiBarisIcu(string $akhiran, ?UnitOrganisasi $pengelola): Aset
    {
        $aset = Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Kategori '.$akhiran])->Id,
            'LokasiId' => $this->icu->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Nama' => 'Printer '.$akhiran,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);

        Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'AsetId' => $aset->Id,
            'LokasiId' => $this->icu->Id,
            'UnitPengelolaId' => $pengelola?->Id,
            'Judul' => 'Keluhan '.$akhiran,
            'Deskripsi' => 'Uji',
        ]);

        PerintahKerja::create([
            'Nomor' => 'PK-'.uniqid(),
            'Jenis' => 'Korektif',
            'Judul' => 'PK '.$akhiran,
            'LokasiId' => $this->icu->Id,
            'UnitPengelolaId' => $pengelola?->Id,
        ]);

        Gudang::create([
            'Kode' => 'GDG-'.uniqid(),
            'Nama' => 'Gudang '.$akhiran,
            'LokasiId' => $this->icu->Id,
            'UnitPengelolaId' => $pengelola?->Id,
        ]);

        return $aset;
    }

    private function berlaku(Pengguna $pengguna, callable $aksi): void
    {
        $this->actingAs($pengguna, 'web');

        try {
            $aksi();
        } finally {
            app('auth')->guard('web')->logout();
        }
    }

    private function buatPengguna(?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Staf',
            'Email' => 'staf+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $this->peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
        ]);

        return $pengguna;
    }
}
