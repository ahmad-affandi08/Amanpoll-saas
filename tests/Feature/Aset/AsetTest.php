<?php

declare(strict_types=1);

namespace Tests\Feature\Aset;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\JenisMeterAset;
use App\Domain\Aset\Domain\Enums\JenisRelasiAset;
use App\Domain\Aset\Domain\Enums\KondisiAset;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Domain\Enums\TingkatKritisAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\NilaiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AsetTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(Organisasi $organisasi, array $kodeIzin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== []) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            foreach ($kodeIzin as $kode) {
                $izin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            }
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }

    private const IZIN_PENUH = ['Aset.Lihat', 'Aset.Buat', 'Aset.Ubah', 'Aset.Hapus'];

    private function buatKategoriAset(Organisasi $organisasi, array $atribut = []): KategoriAset
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $kategori = KategoriAset::create(array_merge([
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Kategori Uji',
        ], $atribut));
        $konteks->bersihkan();

        return $kategori;
    }

    private function buatLokasi(Organisasi $organisasi): Lokasi
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $lokasi = Lokasi::create(['Kode' => 'LOK-'.uniqid(), 'Nama' => 'Lokasi Uji']);
        $konteks->bersihkan();

        return $lokasi;
    }

    private function buatAset(Organisasi $organisasi, KategoriAset $kategori, array $atribut = []): Aset
    {
        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $admin = Pengguna::create([
            'OrganisasiId' => $organisasi->Id, 'Nama' => 'Sistem', 'Email' => 'sistem+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia', 'Status' => 'Aktif',
        ]);
        $aset = Aset::create(array_merge([
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset Uji',
            'Status' => StatusAset::Aktif->value,
            'Kondisi' => KondisiAset::Baik->value,
            'TingkatKritis' => TingkatKritisAset::Normal->value,
            'KodeQr' => (string) Str::ulid(),
            'DibuatOleh' => $admin->Id,
            'Versi' => 1,
        ], $atribut));
        $konteks->bersihkan();

        return $aset;
    }

    // 08.01 KategoriAset

    public function test_kategori_aset_tidak_boleh_hierarki_melingkar(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $induk = $this->buatKategoriAset($organisasi);
        $anak = $this->buatKategoriAset($organisasi, ['IndukId' => $induk->Id]);

        $this->actingAs($pengguna)->put("/aset-master/kategori/{$induk->Id}", [
            'Kode' => $induk->Kode, 'Nama' => $induk->Nama, 'IndukId' => $anak->Id,
        ])->assertStatus(422);
    }

    public function test_kategori_aset_tidak_bisa_dihapus_bila_dipakai_aset(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->delete("/aset-master/kategori/{$kategori->Id}")->assertStatus(422);
    }

    // 08.02 Merek

    public function test_merek_tidak_boleh_duplikat_nama_dalam_organisasi_sama(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);

        $this->actingAs($pengguna)->post('/aset-master/merek', ['Nama' => 'Merek Uji'])->assertSessionDoesntHaveErrors();
        $this->actingAs($pengguna)->post('/aset-master/merek', ['Nama' => 'Merek Uji'])->assertSessionHasErrors('Nama');
    }

    // 08.03 ModelAset

    public function test_model_aset_terhubung_ke_kategori_dan_merek(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $merek = Merek::create(['Nama' => 'Merek Uji']);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post('/aset-master/model', [
            'KategoriAsetId' => $kategori->Id, 'MerekId' => $merek->Id, 'Nama' => 'Model Uji',
        ])->assertSessionDoesntHaveErrors();

        $konteks->tetapkan($organisasi->Id);
        $this->assertDatabaseHas('ModelAset', ['KategoriAsetId' => $kategori->Id, 'MerekId' => $merek->Id, 'Nama' => 'Model Uji']);
        $konteks->bersihkan();
    }

    // 08.04 Asset Registry

    public function test_aset_baru_mewarisi_default_dari_kategori_dan_menulis_riwayat_lokasi_awal(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi, [
            'UmurManfaatBulan' => 60, 'MetodePenyusutanBawaan' => 'GarisLurus', 'PersentaseNilaiResidu' => 10,
        ]);
        $lokasi = $this->buatLokasi($organisasi);

        $response = $this->actingAs($pengguna)->post('/aset', [
            'KategoriAsetId' => $kategori->Id, 'LokasiId' => $lokasi->Id,
            'KodeAset' => 'AST-001', 'Nama' => 'Kompresor Utama',
            'HargaPerolehan' => 100000000, 'Status' => 'Aktif', 'Kondisi' => 'Baik', 'TingkatKritis' => 'Normal',
        ]);
        $response->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $aset = Aset::query()->where('KodeAset', 'AST-001')->firstOrFail();
        $this->assertSame(60, $aset->UmurManfaatBulan);
        $this->assertSame('GarisLurus', $aset->MetodePenyusutan);
        $this->assertEquals(10000000.0, (float) $aset->NilaiResidu);
        $this->assertNotNull($aset->KodeQr);

        $this->assertDatabaseHas('RiwayatLokasiAset', [
            'AsetId' => $aset->Id, 'LokasiAsalId' => null, 'LokasiTujuanId' => $lokasi->Id, 'JenisPerpindahan' => 'Registrasi',
        ]);
        $konteks->bersihkan();
    }

    public function test_kode_aset_unik_per_organisasi(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori, ['KodeAset' => 'AST-DUP']);

        $this->actingAs($pengguna)->post('/aset', [
            'KategoriAsetId' => $kategori->Id, 'KodeAset' => 'AST-DUP', 'Nama' => 'Lainnya',
            'Status' => 'Aktif', 'Kondisi' => 'Baik', 'TingkatKritis' => 'Normal',
        ])->assertSessionHasErrors('KodeAset');
    }

    public function test_ubah_aset_menolak_versi_usang(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->put("/aset/{$aset->Id}", [
            'KategoriAsetId' => $kategori->Id, 'KodeAset' => $aset->KodeAset, 'Nama' => 'Nama Baru',
            'Status' => 'Aktif', 'Kondisi' => 'Baik', 'TingkatKritis' => 'Normal', 'Versi' => 99,
        ])->assertStatus(409);
    }

    public function test_ubah_aset_mengabaikan_perubahan_lokasi_langsung(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $lokasiLain = $this->buatLokasi($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->put("/aset/{$aset->Id}", [
            'KategoriAsetId' => $kategori->Id, 'KodeAset' => $aset->KodeAset, 'Nama' => $aset->Nama,
            'LokasiId' => $lokasiLain->Id, 'Status' => 'Aktif', 'Kondisi' => 'Baik', 'TingkatKritis' => 'Normal', 'Versi' => 1,
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $aset->refresh();
        $this->assertNull($aset->LokasiId);
        $konteks->bersihkan();
    }

    public function test_hapus_aset_adalah_soft_delete(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->delete("/aset/{$aset->Id}")->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $this->assertSoftDeleted($aset);
        $konteks->bersihkan();
    }

    public function test_aset_lintas_organisasi_tidak_bisa_diakses(): void
    {
        $organisasiA = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $penggunaB = $this->buatPengguna($organisasiB, self::IZIN_PENUH);
        $kategoriA = $this->buatKategoriAset($organisasiA);
        $asetA = $this->buatAset($organisasiA, $kategoriA);

        $this->actingAs($penggunaB)->get("/aset/{$asetA->Id}")->assertNotFound();
    }

    // 08.05 RiwayatLokasiAset

    public function test_pindahkan_lokasi_menulis_histori_dan_mengubah_lokasi_saat_ini(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $lokasiAsal = $this->buatLokasi($organisasi);
        $lokasiTujuan = $this->buatLokasi($organisasi);
        $aset = $this->buatAset($organisasi, $kategori, ['LokasiId' => $lokasiAsal->Id]);

        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/riwayat-lokasi", [
            'LokasiTujuanId' => $lokasiTujuan->Id, 'Alasan' => 'Relokasi gudang',
        ])->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $aset->refresh();
        $this->assertSame($lokasiTujuan->Id, $aset->LokasiId);
        $this->assertDatabaseHas('RiwayatLokasiAset', [
            'AsetId' => $aset->Id, 'LokasiAsalId' => $lokasiAsal->Id, 'LokasiTujuanId' => $lokasiTujuan->Id, 'JenisPerpindahan' => 'Manual',
        ]);
        $konteks->bersihkan();
    }

    // 08.06 Penanggung Jawab

    public function test_ganti_penanggung_jawab_menutup_baris_lama_dan_membuat_baris_baru(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);
        $pjLama = $this->buatPengguna($organisasi);
        $pjBaru = $this->buatPengguna($organisasi);

        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/penanggung-jawab", ['PenggunaId' => $pjLama->Id])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/penanggung-jawab", ['PenggunaId' => $pjBaru->Id])
            ->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $riwayat = RiwayatPenanggungJawabAset::query()
            ->where('AsetId', $aset->Id)->orderBy('MulaiPada')->get();
        $this->assertCount(2, $riwayat);
        $this->assertNotNull($riwayat->first()->SelesaiPada);
        $this->assertNull($riwayat->last()->SelesaiPada);
        $this->assertSame($pjBaru->Id, $riwayat->last()->PenggunaId);
        $konteks->bersihkan();
    }

    // 08.07 RelasiAset

    public function test_relasi_aset_menolak_self_reference(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/relasi", [
            'AsetAnakId' => $aset->Id, 'JenisRelasi' => 'Komponen',
        ])->assertStatus(422);
    }

    public function test_relasi_komponen_menolak_hierarki_melingkar(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $asetA = $this->buatAset($organisasi, $kategori, ['KodeAset' => 'AST-A']);
        $asetB = $this->buatAset($organisasi, $kategori, ['KodeAset' => 'AST-B']);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        RelasiAset::create(['AsetIndukId' => $asetA->Id, 'AsetAnakId' => $asetB->Id, 'JenisRelasi' => JenisRelasiAset::Komponen->value]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post("/aset/{$asetB->Id}/relasi", [
            'AsetAnakId' => $asetA->Id, 'JenisRelasi' => 'Komponen',
        ])->assertStatus(422);
    }

    // 08.08 GaransiAset

    public function test_garansi_menandai_akan_berakhir_dalam_ambang_pengingat(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/garansi", [
            'MulaiPada' => now()->subYear()->toDateString(),
            'BerakhirPada' => now()->addDays(10)->toDateString(),
            'Status' => 'Aktif',
        ])->assertSessionDoesntHaveErrors();

        $response = $this->actingAs($pengguna)->get("/aset/{$aset->Id}/garansi");
        $response->assertOk();
        $this->assertTrue($response->json('0.AkanBerakhir'));
    }

    // 08.09 NilaiAset

    public function test_nilai_aset_tidak_boleh_duplikat_tanggal(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $tanggal = now()->toDateString();
        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/nilai", ['TanggalNilai' => $tanggal, 'NilaiBuku' => 1000])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($pengguna)->post("/aset/{$aset->Id}/nilai", ['TanggalNilai' => $tanggal, 'NilaiBuku' => 900])
            ->assertStatus(409);
    }

    public function test_pratinjau_penyusutan_garis_lurus(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori, [
            'HargaPerolehan' => 120000000, 'NilaiResidu' => 0, 'UmurManfaatBulan' => 60,
            'TanggalMulaiOperasi' => now()->subMonths(12)->toDateString(),
        ]);

        $response = $this->actingAs($pengguna)->getJson("/aset/{$aset->Id}/nilai/pratinjau?tanggal=".now()->toDateString());
        $response->assertOk();
        $this->assertEquals(2000000.0, (float) $response->json('BebanPenyusutanPeriode'));
        $this->assertEquals(24000000.0, (float) $response->json('AkumulasiPenyusutan'));
        $this->assertEquals(96000000.0, (float) $response->json('NilaiBuku'));
    }

    // 08.10 Meter

    public function test_meter_kumulatif_menolak_pembacaan_mundur(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $meter = MeterAset::create([
            'AsetId' => $aset->Id, 'Nama' => 'Jam Operasi', 'Satuan' => 'Jam', 'Jenis' => JenisMeterAset::Kumulatif->value, 'NilaiAwal' => 0,
        ]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post("/aset/meter/{$meter->Id}/pembacaan", [
            'Nilai' => 100, 'DibacaPada' => now()->subDay()->toIso8601String(),
        ])->assertSessionDoesntHaveErrors();

        $this->actingAs($pengguna)->post("/aset/meter/{$meter->Id}/pembacaan", [
            'Nilai' => 50, 'DibacaPada' => now()->toIso8601String(),
        ])->assertStatus(422);

        $this->actingAs($pengguna)->post("/aset/meter/{$meter->Id}/pembacaan", [
            'Nilai' => 150, 'DibacaPada' => now()->toIso8601String(),
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_meter_non_kumulatif_boleh_pembacaan_apa_saja(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $pengguna = $this->buatPengguna($organisasi, self::IZIN_PENUH);
        $kategori = $this->buatKategoriAset($organisasi);
        $aset = $this->buatAset($organisasi, $kategori);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);
        $meter = MeterAset::create([
            'AsetId' => $aset->Id, 'Nama' => 'Suhu', 'Satuan' => 'Celcius', 'Jenis' => JenisMeterAset::NonKumulatif->value, 'NilaiAwal' => 0,
        ]);
        $konteks->bersihkan();

        $this->actingAs($pengguna)->post("/aset/meter/{$meter->Id}/pembacaan", ['Nilai' => 80, 'DibacaPada' => now()->toIso8601String()])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($pengguna)->post("/aset/meter/{$meter->Id}/pembacaan", ['Nilai' => 20, 'DibacaPada' => now()->toIso8601String()])
            ->assertSessionDoesntHaveErrors();
    }
}
