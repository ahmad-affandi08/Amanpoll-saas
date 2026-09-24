<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Application\Actions\UbahUnitOrganisasi;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;
use App\Domain\Platform\Http\Requests\UnitPengelolaSah;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tanda Mengelola Aset pada Unit Organisasi dan fondasi pemakainya (PRD 8.21):
 * aturan validasi bersama, sumber opsi formulir, dan penolakan mencabut tanda
 * selama unit masih menjadi unit pengelola.
 */
class UnitPengelolaUnitOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-UPO', 'Nama' => 'RS Uji', 'Status' => 'Aktif']);
        $this->konteks()->tetapkan($this->organisasi->Id);

        $this->admin = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        $izin = Izin::firstOrCreate(['Kode' => 'Pengaturan.Kelola'], ['Nama' => 'Kelola Pengaturan', 'Modul' => 'Sistem']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $this->admin->Id, 'PeranId' => $peran->Id]);
    }

    public function test_tanda_mengelola_aset_tersimpan_dan_tampil_di_daftar(): void
    {
        $this->actingAs($this->admin)->post('/platform/unit-organisasi', [
            'Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->assertDatabaseHas('UnitOrganisasi', ['Kode' => 'IT', 'MengelolaAset' => true]);

        $this->actingAs($this->admin)->get('/platform/unit-organisasi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->where('unitOrganisasi.data.0.MengelolaAset', true)
                ->etc());
    }

    /** Pemanggil yang tidak mengirim isiannya tidak diam-diam mencabut tanda. */
    public function test_ubah_tanpa_isian_mengelola_aset_mempertahankan_tanda(): void
    {
        $it = $this->buatUnit('IT', true);

        $this->ubah($it, [])->assertSessionDoesntHaveErrors();

        $this->assertTrue($it->fresh()?->MengelolaAset);
    }

    public function test_tanda_dapat_dicabut_bila_unit_tidak_dipakai(): void
    {
        $it = $this->buatUnit('IT', true);
        // Tiket final tidak menghalangi: riwayatnya memang milik unit ini.
        $this->buatKeluhan($it, 'Ditutup');
        $this->buatPerintahKerja($it, 'Dibatalkan');

        $this->ubah($it, ['MengelolaAset' => false])->assertSessionDoesntHaveErrors();

        $this->assertFalse($it->fresh()?->MengelolaAset);
    }

    public function test_mencabut_tanda_ditolak_selama_masih_dipakai_dan_pesannya_menyebut_pemakai(): void
    {
        $it = $this->buatUnit('IT', true);
        $this->buatAset($it);
        KategoriKeluhan::create(['Nama' => 'Jaringan', 'UnitPengelolaId' => $it->Id]);
        Gudang::create(['Kode' => 'GDG-IT', 'Nama' => 'Gudang IT', 'UnitPengelolaId' => $it->Id]);
        $this->buatKeluhan($it, 'Selesai');
        $this->buatPerintahKerja($it, 'Dikerjakan');

        $this->ubah($it, ['MengelolaAset' => false])
            ->assertSessionHasErrors([
                'MengelolaAset' => 'Tanda Mengelola Aset tidak dapat dicabut: unit ini masih menjadi unit pengelola '
                    .'1 aset, 1 kategori keluhan, 1 gudang, 1 keluhan yang belum final, 1 perintah kerja yang belum final. '
                    .'Pindahkan dulu ke unit pengelola lain.',
            ]);

        $this->assertTrue($it->fresh()?->MengelolaAset);
    }

    /** Satu pemakai saja sudah cukup untuk menolak; tiap jenis dihitung. */
    public function test_satu_perintah_kerja_terbuka_saja_menolak_pencabutan(): void
    {
        $it = $this->buatUnit('IT', true);
        $this->buatPerintahKerja($it, 'Selesai');

        $this->ubah($it, ['MengelolaAset' => false])->assertSessionHasErrors('MengelolaAset');
        $this->assertTrue($it->fresh()?->MengelolaAset);
    }

    /** Penjaga terakhir di Action, untuk pemanggil yang tidak lewat FormRequest. */
    public function test_action_ubah_menolak_pencabutan_yang_masih_dipakai(): void
    {
        $it = $this->buatUnit('IT', true);
        $this->buatAset($it);

        try {
            app(UbahUnitOrganisasi::class)->jalankan($it, ['MengelolaAset' => false]);
            $this->fail('Seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $e) {
            $this->assertStringContainsString('1 aset', $e->getMessage());
        }

        $this->assertTrue($it->fresh()?->MengelolaAset);
    }

    public function test_menghapus_unit_yang_masih_menjadi_unit_pengelola_ditolak(): void
    {
        $it = $this->buatUnit('IT', true);
        Gudang::create(['Kode' => 'GDG-IT', 'Nama' => 'Gudang IT', 'UnitPengelolaId' => $it->Id]);

        $this->actingAs($this->admin)->delete('/platform/unit-organisasi/'.$it->Id)->assertStatus(422);

        $this->konteks()->tetapkan($this->organisasi->Id);
        $this->assertNotSoftDeleted($it);
    }

    public function test_aturan_unit_pengelola_menerima_unit_aktif_bertanda(): void
    {
        $it = $this->buatUnit('IT', true);

        $this->assertNull($this->galat($it->Id));
        $this->assertNull($this->galat(null));
        $this->assertNull($this->galat(''));
    }

    public function test_aturan_unit_pengelola_menolak_unit_tidak_sah(): void
    {
        $bukanPengelola = $this->buatUnit('ICU', false);
        $nonaktif = $this->buatUnit('LAMA', true, 'Nonaktif');
        $terhapus = $this->buatUnit('HAPUS', true);
        $terhapus->delete();

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        $this->konteks()->tetapkan($lain->Id);
        $milikLain = $this->buatUnit('IT-LAIN', true);
        $this->konteks()->tetapkan($this->organisasi->Id);

        $this->assertSame('Unit yang dipilih belum ditandai Mengelola Aset di halaman Unit Organisasi.', $this->galat($bukanPengelola->Id));
        $this->assertSame('Unit pengelola yang dipilih sedang nonaktif.', $this->galat($nonaktif->Id));
        $this->assertSame('Unit pengelola yang dipilih tidak ditemukan.', $this->galat($terhapus->Id));
        $this->assertSame('Unit pengelola yang dipilih tidak ditemukan.', $this->galat($milikLain->Id));
        $this->assertNotNull($this->galat(['bukan', 'teks']));
    }

    /** Menyimpan ulang baris yang unit pengelolanya kini nonaktif tidak boleh ditolak. */
    public function test_aturan_unit_pengelola_menerima_nilai_tersimpan_yang_kini_nonaktif(): void
    {
        $nonaktif = $this->buatUnit('LAMA', true, 'Nonaktif');
        $lainNonaktif = $this->buatUnit('LAMA2', true, 'Nonaktif');

        $this->assertNull($this->galat($nonaktif->Id, $nonaktif->Id));
        $this->assertSame('Unit pengelola yang dipilih sedang nonaktif.', $this->galat($lainNonaktif->Id, $nonaktif->Id));
    }

    /** Opsi sama bagi setiap pengguna: lepas dari ScopeLingkup, tetap terikat tenancy. */
    public function test_opsi_unit_pengelola_hanya_unit_aktif_bertanda_organisasi_ini(): void
    {
        $it = $this->buatUnit('IT', true);
        $ipsrs = $this->buatUnit('IPS', true);
        $this->buatUnit('ICU', false);
        $nonaktif = $this->buatUnit('LAMA', true, 'Nonaktif');

        $lain = Organisasi::create(['Kode' => 'ORG-LAIN', 'Nama' => 'RS Lain', 'Status' => 'Aktif']);
        $this->konteks()->tetapkan($lain->Id);
        $this->buatUnit('IT-LAIN', true);
        $this->konteks()->tetapkan($this->organisasi->Id);

        // Pengguna berlingkup ruangan: UnitOrganisasi berscope tidak memuat satu pun unit.
        $perawat = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Perawat',
            'Email' => 'perawat+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
        PenggunaPeran::create([
            'PenggunaId' => $perawat->Id,
            'PeranId' => Peran::create(['Kode' => 'PRW', 'Nama' => 'Perawat'])->Id,
            'LokasiId' => Lokasi::create(['Kode' => 'R1', 'Nama' => 'ICU'])->Id,
        ]);
        $this->actingAs($perawat, 'web');
        $this->assertSame(0, UnitOrganisasi::query()->count(), 'Prasyarat: unit di luar lingkup perawat.');

        $this->assertSame([$ipsrs->Id, $it->Id], array_column(OpsiUnitPengelola::daftar(), 'Id'));
        $this->assertSame(['Id' => $ipsrs->Id, 'Kode' => 'IPS', 'Nama' => 'Unit IPS'], OpsiUnitPengelola::daftar()[0]);
        $this->assertSame([$ipsrs->Id, $it->Id, $nonaktif->Id], array_column(OpsiUnitPengelola::daftar($nonaktif->Id), 'Id'));
        $this->assertCount(3, OpsiUnitPengelola::daftar(termasukNonaktif: true));
        $this->assertTrue(OpsiUnitPengelola::dipakai());
    }

    public function test_organisasi_tanpa_unit_bertanda_tidak_memakai_fitur(): void
    {
        $this->buatUnit('ICU', false);

        $this->assertSame([], OpsiUnitPengelola::daftar());
        $this->assertFalse(OpsiUnitPengelola::dipakai());
    }

    private function konteks(): KonteksOrganisasi
    {
        return app(KonteksOrganisasi::class);
    }

    /** @param  array<string, mixed>  $perubahan */
    private function ubah(UnitOrganisasi $unit, array $perubahan): TestResponse
    {
        return $this->actingAs($this->admin)->put('/platform/unit-organisasi/'.$unit->Id, [
            'Kode' => $unit->Kode,
            'Nama' => $unit->Nama,
            'Jenis' => $unit->Jenis,
            'Status' => $unit->Status,
            ...$perubahan,
        ]);
    }

    private function galat(mixed $nilai, ?string $nilaiTersimpan = null): ?string
    {
        $validator = Validator::make(
            ['UnitPengelolaId' => $nilai],
            ['UnitPengelolaId' => UnitPengelolaSah::aturan($nilaiTersimpan)],
        );

        return $validator->errors()->first('UnitPengelolaId') ?: null;
    }

    private function buatUnit(string $kode, bool $mengelolaAset, string $status = 'Aktif'): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'Kode' => $kode,
            'Nama' => 'Unit '.$kode,
            'Jenis' => 'Instalasi',
            'Status' => $status,
            'MengelolaAset' => $mengelolaAset,
        ]);
    }

    private function buatAset(UnitOrganisasi $pengelola): Aset
    {
        return Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Alat '.uniqid()])->Id,
            'UnitPengelolaId' => $pengelola->Id,
            'Nama' => 'Aset Uji',
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    private function buatKeluhan(UnitOrganisasi $pengelola, string $status): Keluhan
    {
        return Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'UnitPengelolaId' => $pengelola->Id,
            'Judul' => 'Rusak',
            'Deskripsi' => 'Uji',
            'Status' => $status,
        ]);
    }

    private function buatPerintahKerja(UnitOrganisasi $pengelola, string $status): PerintahKerja
    {
        return PerintahKerja::create([
            'Nomor' => 'PK-'.uniqid(),
            'Jenis' => 'Korektif',
            'Judul' => 'Perbaikan',
            'UnitPengelolaId' => $pengelola->Id,
            'Status' => $status,
        ]);
    }
}
