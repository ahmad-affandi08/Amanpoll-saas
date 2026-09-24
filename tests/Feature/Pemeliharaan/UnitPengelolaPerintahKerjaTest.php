<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Pemeliharaan\Application\Actions\AlihkanUnitPengelolaPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\BuatPerintahKerja;
use App\Domain\Pemeliharaan\Application\Actions\TugaskanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Application\Services\PemakaianUnitPengelola;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\UnitOrganisasi;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Unit pengelola pada perintah kerja, preventif, dan kalibrasi (PRD 8.21, TASK 40.04).
 *
 * Dua bagian dalam satu rumah sakit: IT dan IPSRS. Printer di ICU dikelola IT,
 * ventilator di ICU dikelola IPSRS. Perintah kerja mewarisi unit pengelolanya
 * dari isian → keluhan → aset → rencana, dari jalur mana pun ia dibuat, dan
 * hanya teknisi yang lingkupnya mencakup tiket yang boleh ditugaskan.
 */
final class UnitPengelolaPerintahKerjaTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private UnitOrganisasi $it;

    private UnitOrganisasi $ipsrs;

    /** Unit pemakai; tidak bertanda Mengelola Aset. */
    private UnitOrganisasi $icu;

    private Lokasi $ruangIcu;

    private Aset $printer;

    private Aset $ventilator;

    private Pengguna $koordinator;

    private Pengguna $teknisiIt;

    private Pengguna $teknisiIpsrs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisasi = Organisasi::create(['Kode' => 'ORG-UPK', 'Nama' => 'RS Dua Bagian', 'Status' => 'Aktif']);
        $this->konteks();
        $this->siapkanNomorDokumen();

        $this->it = UnitOrganisasi::create(['Kode' => 'IT', 'Nama' => 'Instalasi IT', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $this->ipsrs = UnitOrganisasi::create(['Kode' => 'IPS', 'Nama' => 'IPSRS', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $this->icu = UnitOrganisasi::create(['Kode' => 'ICU', 'Nama' => 'ICU', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);
        $this->ruangIcu = Lokasi::create(['Kode' => 'R-ICU', 'Nama' => 'Ruang ICU', 'UnitOrganisasiId' => $this->icu->Id, 'Status' => 'Aktif']);

        $this->printer = $this->buatAset('Printer ICU', $this->it);
        $this->ventilator = $this->buatAset('Ventilator ICU', $this->ipsrs);

        $this->koordinator = $this->buatPengguna('Koordinator', ['PerintahKerja.Kelola', 'Pemeliharaan.Kelola', 'Kalibrasi.Kelola']);
        $this->teknisiIt = $this->buatPengguna('Teknisi IT', ['Pemeliharaan.Kelola'], unit: $this->it);
        $this->teknisiIpsrs = $this->buatPengguna('Teknisi IPSRS', ['Pemeliharaan.Kelola'], unit: $this->ipsrs);
    }

    public function test_perintah_kerja_dari_keluhan_it_masuk_antrian_it_walau_asetnya_milik_ipsrs(): void
    {
        $keluhan = $this->buatKeluhan($this->ventilator, $this->it);

        $this->kirimPerintahKerja(['KeluhanId' => $keluhan->Id])->assertSessionHasNoErrors();

        $perintahKerja = PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->firstOrFail();
        $this->assertSame($this->it->Id, $perintahKerja->UnitPengelolaId);
        $this->assertSame($this->icu->Id, $perintahKerja->UnitOrganisasiId, 'Unit organisasi yang kosong diisi dari aset keluhan.');
    }

    public function test_keluhan_tanpa_unit_pengelola_diturunkan_dari_asetnya(): void
    {
        $keluhan = $this->buatKeluhan($this->printer, null);

        $this->kirimPerintahKerja(['KeluhanId' => $keluhan->Id])->assertSessionHasNoErrors();

        $this->assertSame($this->it->Id, PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->value('UnitPengelolaId'));
    }

    public function test_formulir_tanpa_isian_mewarisi_unit_pengelola_dan_unit_organisasi_aset_utama(): void
    {
        $this->kirimPerintahKerja(['Judul' => 'Printer macet', 'LokasiId' => $this->ruangIcu->Id, 'AsetIds' => [$this->printer->Id, $this->ventilator->Id]])
            ->assertSessionHasNoErrors();

        $perintahKerja = PerintahKerja::query()->where('Judul', 'Printer macet')->firstOrFail();
        $this->assertSame($this->it->Id, $perintahKerja->UnitPengelolaId);
        $this->assertSame($this->icu->Id, $perintahKerja->UnitOrganisasiId);
    }

    public function test_isian_eksplisit_menang_atas_keluhan_dan_aset(): void
    {
        $keluhan = $this->buatKeluhan($this->printer, $this->it);
        $unitLain = UnitOrganisasi::create(['Kode' => 'KSL', 'Nama' => 'Kesling', 'Jenis' => 'Instalasi', 'Status' => 'Aktif']);

        $this->kirimPerintahKerja(['KeluhanId' => $keluhan->Id, 'UnitPengelolaId' => $this->ipsrs->Id, 'UnitOrganisasiId' => $unitLain->Id])
            ->assertSessionHasNoErrors();

        $perintahKerja = PerintahKerja::query()->where('KeluhanId', $keluhan->Id)->firstOrFail();
        $this->assertSame($this->ipsrs->Id, $perintahKerja->UnitPengelolaId);
        $this->assertSame($unitLain->Id, $perintahKerja->UnitOrganisasiId, 'Unit organisasi yang diisi tidak ditimpa aset.');
    }

    public function test_isian_unit_yang_bukan_unit_pengelola_ditolak(): void
    {
        $this->kirimPerintahKerja(['Judul' => 'Salah unit', 'LokasiId' => $this->ruangIcu->Id, 'AsetIds' => [$this->printer->Id], 'UnitPengelolaId' => $this->icu->Id])
            ->assertSessionHasErrors('UnitPengelolaId');

        $this->assertFalse(PerintahKerja::query()->where('Judul', 'Salah unit')->exists());
    }

    /** Penjaga terakhir di Action: jalur selain formulir (impor, Mode Lapangan) tidak lolos dengan unit yang tidak sah. */
    public function test_action_menolak_isian_unit_yang_bukan_unit_pengelola(): void
    {
        try {
            app(BuatPerintahKerja::class)->jalankan([
                'Jenis' => 'Korektif', 'Judul' => 'Lewat action', 'Prioritas' => 'Normal',
                'LokasiId' => $this->ruangIcu->Id, 'AsetIds' => [$this->printer->Id], 'UnitPengelolaId' => $this->icu->Id,
            ], $this->koordinator->Id);
            $this->fail('Unit yang tidak bertanda Mengelola Aset seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('Mengelola Aset', $galat->getMessage());
        }

        $this->assertFalse(PerintahKerja::query()->where('Judul', 'Lewat action')->exists());
    }

    public function test_preventif_mewarisi_unit_aset_lalu_rencana_sebagai_cadangan(): void
    {
        $asetTanpaUnit = $this->buatAset('Kursi Roda', null);
        $rencana = app(KelolaRencanaPemeliharaan::class)->buat([
            'Nama' => 'PM Bulanan', 'IntervalNilai' => 1, 'IntervalSatuan' => 'Bulan', 'UnitPengelolaId' => $this->ipsrs->Id,
        ], $this->koordinator->Id);
        $hariIni = CarbonImmutable::today()->toDateString();
        app(KelolaRencanaPemeliharaan::class)->tetapkanAset($rencana, $this->printer->Id, $hariIni, $hariIni);
        app(KelolaRencanaPemeliharaan::class)->tetapkanAset($rencana, $asetTanpaUnit->Id, $hariIni, $hariIni);

        $hasil = app(JadwalkanPemeliharaanPreventif::class)->jalankan(
            tanggalAcuan: CarbonImmutable::today(),
            organisasiId: $this->organisasi->Id,
            penggunaId: $this->koordinator->Id,
        );

        $this->assertSame(2, $hasil['perintahKerjaDibuat']);
        $this->assertSame($this->it->Id, $this->perintahKerjaUntukAset($this->printer)->UnitPengelolaId, 'Unit pengelola aset didahulukan.');
        $this->assertSame($this->ipsrs->Id, $this->perintahKerjaUntukAset($asetTanpaUnit)->UnitPengelolaId, 'Aset tanpa unit pengelola memakai unit rencana.');
        $this->assertSame($this->icu->Id, $this->perintahKerjaUntukAset($this->printer)->UnitOrganisasiId);
    }

    public function test_tindak_lanjut_inspeksi_mewarisi_unit_pengelola_aset(): void
    {
        $kelola = app(KelolaInspeksi::class);
        $daftarPeriksa = app(KelolaTemplatDaftarPeriksa::class)->buat(['Nama' => 'Checklist Printer'], $this->koordinator->Id);
        $templat = $kelola->buatTemplat(['Nama' => 'Inspeksi Printer', 'TemplatDaftarPeriksaId' => $daftarPeriksa->Id], $this->koordinator->Id);
        $inspeksi = $kelola->jadwalkan(['TemplatInspeksiId' => $templat->Id, 'AsetId' => $this->printer->Id], $this->koordinator->Id);

        $perintahKerja = $kelola->buatPerintahKerjaKorektif($inspeksi, [], $this->koordinator->Id);

        $this->assertSame($this->it->Id, $perintahKerja->fresh()?->UnitPengelolaId);
        $this->assertSame($this->icu->Id, $perintahKerja->fresh()?->UnitOrganisasiId);
    }

    public function test_pilihan_teknisi_hanya_berisi_pengguna_yang_lingkupnya_mencakup_tiket_it(): void
    {
        $perintahKerja = $this->buatPerintahKerja($this->printer);
        $penggunaNonaktif = $this->buatPengguna('Teknisi Nonaktif', ['Pemeliharaan.Kelola'], unit: $this->it);
        $penggunaNonaktif->update(['Status' => 'Nonaktif']);

        $respons = $this->actingAs($this->koordinator)->get('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id)->assertOk();

        $idTeknisi = array_column($this->propHalaman($respons, 'teknisi'), 'Id');
        $this->assertContains($this->teknisiIt->Id, $idTeknisi);
        $this->assertContains($this->koordinator->Id, $idTeknisi, 'Pengguna tanpa lingkup melihat seluruh organisasi, jadi tetap dapat ditugaskan.');
        $this->assertNotContains($this->teknisiIpsrs->Id, $idTeknisi);
        $this->assertNotContains($penggunaNonaktif->Id, $idTeknisi);
        $this->assertSame(1, $this->propHalaman($respons, 'jumlahTeknisiDiluarLingkup'));
    }

    public function test_server_menolak_menugaskan_teknisi_ipsrs_ke_perintah_kerja_it(): void
    {
        $perintahKerja = $this->buatPerintahKerja($this->printer);

        $this->actingAs($this->koordinator)
            ->post('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/penugasan', [
                'PenggunaIds' => [$this->teknisiIpsrs->Id],
                'PeranTugas' => 'Anggota',
            ])
            ->assertSessionHasErrors(['PenggunaIds' => 'Lingkup akses Teknisi IPSRS tidak mencakup perintah kerja ini, sehingga tiketnya tidak dapat dibuka. Pilih teknisi yang lingkupnya mencakup unit pengelola Instalasi IT.']);

        $this->assertFalse(PenugasanPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->exists());
    }

    /** Penjaga terakhir di Action, bila FormRequest terlewati. */
    public function test_action_penugasan_menolak_teknisi_yang_tidak_dapat_melihat_tiket(): void
    {
        $this->konteks();
        $perintahKerja = $this->buatPerintahKerja($this->printer);

        try {
            app(TugaskanPerintahKerja::class)->jalankan($perintahKerja, [$this->teknisiIt->Id, $this->teknisiIpsrs->Id], 'Anggota', false, $this->koordinator->Id);
            $this->fail('Teknisi IPSRS seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('Teknisi IPSRS', $galat->getMessage());
            $this->assertStringNotContainsString('Teknisi IT', $galat->getMessage());
        }

        $this->assertFalse(PenugasanPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->exists(), 'Tidak ada penugasan sebagian.');
    }

    public function test_teknisi_it_dapat_ditugaskan_ke_perintah_kerja_it(): void
    {
        $perintahKerja = $this->buatPerintahKerja($this->printer);

        $this->actingAs($this->koordinator)
            ->post('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/penugasan', [
                'PenggunaIds' => [$this->teknisiIt->Id],
                'PeranTugas' => 'Anggota',
            ])
            ->assertSessionHasNoErrors();

        $this->konteks();
        $this->assertTrue(PenugasanPerintahKerja::query()->where('PerintahKerjaId', $perintahKerja->Id)->where('PenggunaId', $this->teknisiIt->Id)->exists());
    }

    public function test_organisasi_tanpa_unit_pengelola_tidak_berubah(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-SATU', 'Nama' => 'RS Satu Bagian', 'Status' => 'Aktif']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'Awalan' => 'WO', 'FormatNomor' => '{Awalan}-{Nomor:4}', 'ResetPeriode' => 'Tahunan']);
        $lokasi = Lokasi::create(['Kode' => 'R-1', 'Nama' => 'Ruang 1', 'Status' => 'Aktif']);
        $aset = Aset::create(['KategoriAsetId' => KategoriAset::create(['Nama' => 'Umum'])->Id, 'LokasiId' => $lokasi->Id, 'Nama' => 'Pompa', 'Status' => 'Aktif', 'Kondisi' => 'Baik']);
        $koordinator = $this->buatPengguna('Koordinator Satu', ['PerintahKerja.Kelola']);
        $teknisi = $this->buatPengguna('Teknisi Satu', ['Pemeliharaan.Kelola']);

        $this->actingAs($koordinator)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif', 'Prioritas' => 'Normal', 'Judul' => 'Pompa bocor', 'LokasiId' => $lokasi->Id,
            'AsetIds' => [$aset->Id], 'MembutuhkanWaktuHenti' => false, 'MembutuhkanPersetujuan' => false,
        ])->assertSessionHasNoErrors();

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $perintahKerja = PerintahKerja::query()->where('Judul', 'Pompa bocor')->firstOrFail();
        $this->assertNull($perintahKerja->UnitPengelolaId);

        $respons = $this->actingAs($koordinator)->get('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id)->assertOk();
        $this->assertFalse($this->propHalaman($respons, 'unitPengelolaDipakai'));
        $this->assertSame(0, $this->propHalaman($respons, 'jumlahTeknisiDiluarLingkup'));
        $this->assertEqualsCanonicalizing([$koordinator->Id, $teknisi->Id], array_column($this->propHalaman($respons, 'teknisi'), 'Id'));

        $this->actingAs($koordinator)->post('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/penugasan', [
            'PenggunaIds' => [$teknisi->Id], 'PeranTugas' => 'Anggota',
        ])->assertSessionHasNoErrors();

        $this->actingAs($koordinator)->get('/pemeliharaan/perintah-kerja')->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->where('unitPengelolaDipakai', false)->where('saringanUnitPengelola', [])->etc());
    }

    public function test_daftar_perintah_kerja_dapat_disaring_menurut_unit_pengelola(): void
    {
        $milikIt = $this->buatPerintahKerja($this->printer);
        $milikIpsrs = $this->buatPerintahKerja($this->ventilator);

        $respons = $this->actingAs($this->koordinator)->get('/pemeliharaan/perintah-kerja?unitPengelola='.$this->it->Id)->assertOk();

        $baris = $this->propHalaman($respons, 'perintahKerja')['data'];
        $this->assertSame([$milikIt->Id], array_column($baris, 'Id'));
        $this->assertSame('Instalasi IT', $baris[0]['UnitPengelola']['Nama']);
        $this->assertNotSame($milikIt->Id, $milikIpsrs->Id);
        $this->assertTrue($this->propHalaman($respons, 'unitPengelolaDipakai'));
    }

    public function test_alihkan_ditolak_selama_teknisi_aktif_tidak_mencakup_unit_tujuan(): void
    {
        $perintahKerja = $this->buatPerintahKerja($this->ventilator);
        app(TugaskanPerintahKerja::class)->jalankan($perintahKerja, [$this->teknisiIpsrs->Id], 'Anggota', false, $this->koordinator->Id);

        $this->actingAs($this->koordinator)
            ->put('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/unit-pengelola', ['UnitPengelolaId' => $this->it->Id, 'Alasan' => 'Salah antrian'])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Perintah kerja masih ditugaskan kepada Teknisi IPSRS, yang lingkup aksesnya tidak mencakup unit pengelola tujuan sehingga tiketnya akan hilang dari layarnya. Ganti penugasannya lebih dulu, atau pilih unit pengelola lain.']);

        $this->konteks();
        $this->assertSame($this->ipsrs->Id, $perintahKerja->fresh()?->UnitPengelolaId);

        try {
            app(AlihkanUnitPengelolaPerintahKerja::class)->jalankan($perintahKerja, $this->it->Id, 'Salah antrian');
            $this->fail('Action seharusnya menolak juga.');
        } catch (AturanBisnisDilanggar) {
            // diharapkan
        }

        $this->assertSame($this->ipsrs->Id, $perintahKerja->fresh()?->UnitPengelolaId);
    }

    /** Teknisi yang tetap dapat melihat tiket (lingkupnya ruangan ICU) tidak menghalangi pengalihan. */
    public function test_alihkan_berhasil_bila_teknisi_aktif_tetap_mencakup_tiket_dan_tercatat_di_audit(): void
    {
        $teknisiIcu = $this->buatPengguna('Teknisi Ruang ICU', ['Pemeliharaan.Kelola'], lokasi: $this->ruangIcu);
        $perintahKerja = $this->buatPerintahKerja($this->ventilator);
        app(TugaskanPerintahKerja::class)->jalankan($perintahKerja, [$teknisiIcu->Id], 'Anggota', false, $this->koordinator->Id);
        $versiSebelum = $perintahKerja->fresh()?->Versi;

        $this->actingAs($this->koordinator)
            ->put('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/unit-pengelola', ['UnitPengelolaId' => $this->it->Id, 'Alasan' => 'Ventilator ini disambung ke jaringan IT'])
            ->assertSessionHasNoErrors();

        $this->konteks();
        $segar = $perintahKerja->fresh();
        $this->assertSame($this->it->Id, $segar?->UnitPengelolaId);
        $this->assertSame($versiSebelum, $segar?->Versi, 'Versi tidak naik supaya pembaruan offline teknisi tidak dianggap konflik.');
        $this->assertTrue(DB::table('CatatanAudit')->where('Aksi', 'AlihkanUnitPengelola')->where('EntitasId', $perintahKerja->Id)
            ->where('DataSesudah', 'like', '%Ventilator ini disambung%')->exists());
    }

    public function test_alihkan_perintah_kerja_final_ditolak_dan_teknisi_tidak_boleh_mengalihkan(): void
    {
        $perintahKerja = $this->buatPerintahKerja($this->printer);
        app(TugaskanPerintahKerja::class)->jalankan($perintahKerja, [$this->teknisiIt->Id], 'Anggota', false, $this->koordinator->Id);

        $this->actingAs($this->teknisiIt)
            ->put('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/unit-pengelola', ['UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Coba'])
            ->assertForbidden();

        $this->konteks();
        $perintahKerja->forceFill(['Status' => 'Ditutup'])->save();

        $this->actingAs($this->koordinator)
            ->put('/pemeliharaan/perintah-kerja/'.$perintahKerja->Id.'/unit-pengelola', ['UnitPengelolaId' => $this->ipsrs->Id, 'Alasan' => 'Terlambat'])
            ->assertSessionHasErrors(['UnitPengelolaId' => 'Perintah kerja yang sudah ditutup atau dibatalkan tidak dapat dialihkan.']);

        $this->konteks();
        $this->assertSame($this->it->Id, $perintahKerja->fresh()?->UnitPengelolaId);
    }

    public function test_rencana_pemeliharaan_menyimpan_unit_pengelola_dan_menyarankan_dari_aset(): void
    {
        $this->actingAs($this->koordinator)->post('/preventif-inspeksi/rencana-pemeliharaan', [
            'Nama' => 'PM Printer', 'IntervalNilai' => 3, 'IntervalSatuan' => 'Bulan',
        ])->assertSessionHasNoErrors();

        $this->konteks();
        $rencana = RencanaPemeliharaan::query()->where('Nama', 'PM Printer')->firstOrFail();
        $this->assertNull($rencana->UnitPengelolaId);
        $kelola = app(KelolaRencanaPemeliharaan::class);
        $kelola->tetapkanAset($rencana, $this->printer->Id);

        $respons = $this->actingAs($this->koordinator)->get('/preventif-inspeksi/rencana-pemeliharaan/'.$rencana->Id)->assertOk();
        $this->assertSame($this->it->Id, $this->propHalaman($respons, 'saranUnitPengelolaId'), 'Semua aset dikelola IT.');

        $this->actingAs($this->koordinator)->put('/preventif-inspeksi/rencana-pemeliharaan/'.$rencana->Id, [
            'Nama' => 'PM Printer', 'IntervalNilai' => 3, 'IntervalSatuan' => 'Bulan', 'UnitPengelolaId' => $this->it->Id,
        ])->assertSessionHasNoErrors();
        $this->konteks();
        $this->assertSame($this->it->Id, $rencana->fresh()?->UnitPengelolaId);

        $this->actingAs($this->koordinator)->put('/preventif-inspeksi/rencana-pemeliharaan/'.$rencana->Id, [
            'Nama' => 'PM Printer Baru', 'IntervalNilai' => 3, 'IntervalSatuan' => 'Bulan',
        ])->assertSessionHasNoErrors();
        $this->konteks();
        $this->assertSame($this->it->Id, $rencana->fresh()?->UnitPengelolaId, 'Pemanggil yang tidak mengirim isiannya tidak mengosongkannya.');

        $kelola->tetapkanAset($rencana, $this->ventilator->Id);
        $respons = $this->actingAs($this->koordinator)->get('/preventif-inspeksi/rencana-pemeliharaan/'.$rencana->Id)->assertOk();
        $this->assertNull($this->propHalaman($respons, 'saranUnitPengelolaId'), 'Aset dari dua unit pengelola: tidak ada saran.');

        $this->actingAs($this->koordinator)->post('/preventif-inspeksi/rencana-pemeliharaan', [
            'Nama' => 'PM Salah', 'IntervalNilai' => 1, 'IntervalSatuan' => 'Bulan', 'UnitPengelolaId' => $this->icu->Id,
        ])->assertSessionHasErrors('UnitPengelolaId');
    }

    public function test_rencana_kalibrasi_menyimpan_unit_pengelola_dan_dapat_disaring(): void
    {
        $this->actingAs($this->koordinator)->post('/kalibrasi/rencana', [
            'AsetId' => $this->printer->Id, 'IntervalHari' => 365, 'TanggalMulai' => '2026-09-01', 'UnitPengelolaId' => $this->it->Id,
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->koordinator)->post('/kalibrasi/rencana', [
            'AsetId' => $this->ventilator->Id, 'IntervalHari' => 365, 'TanggalMulai' => '2026-09-01', 'UnitPengelolaId' => $this->ipsrs->Id,
        ])->assertSessionHasNoErrors();

        $this->konteks();
        $milikIt = RencanaKalibrasi::query()->where('AsetId', $this->printer->Id)->firstOrFail();
        $this->assertSame($this->it->Id, $milikIt->UnitPengelolaId);

        $respons = $this->actingAs($this->koordinator)->get('/kalibrasi/rencana?unitPengelolaId='.$this->it->Id)->assertOk();
        $this->assertSame([$milikIt->Id], array_column($this->propHalaman($respons, 'rencanaKalibrasi'), 'Id'));

        $this->actingAs($this->koordinator)->post('/kalibrasi/rencana', [
            'AsetId' => $this->printer->Id, 'IntervalHari' => 30, 'TanggalMulai' => '2026-09-01', 'UnitPengelolaId' => $this->icu->Id,
        ])->assertSessionHasErrors('UnitPengelolaId');
    }

    /** Rencana ikut dihitung: tanda yang dicabut membuat tiket preventifnya mewarisi unit yang bukan unit pengelola lagi. */
    public function test_rencana_yang_memakai_unit_menahan_pencabutan_tanda_mengelola_aset(): void
    {
        $unitBaru = UnitOrganisasi::create(['Kode' => 'KSL', 'Nama' => 'Kesling', 'Jenis' => 'Instalasi', 'Status' => 'Aktif', 'MengelolaAset' => true]);
        $pemakaian = app(PemakaianUnitPengelola::class);
        $this->assertNull($pemakaian->alasanTolakCabut($unitBaru->Id));

        app(KelolaRencanaPemeliharaan::class)->buat(['Nama' => 'PM Kesling', 'IntervalNilai' => 1, 'IntervalSatuan' => 'Bulan', 'UnitPengelolaId' => $unitBaru->Id], $this->koordinator->Id);
        RencanaKalibrasi::create(['AsetId' => $this->printer->Id, 'UnitPengelolaId' => $unitBaru->Id, 'IntervalHari' => 30, 'TanggalMulai' => '2026-09-01', 'TanggalBerikutnya' => '2026-10-01']);

        $this->assertSame(['rencana pemeliharaan' => 1, 'rencana kalibrasi' => 1], $pemakaian->hitung($unitBaru->Id));
    }

    /** @param array<string, mixed> $data */
    private function kirimPerintahKerja(array $data): TestResponse
    {
        $respons = $this->actingAs($this->koordinator)->post('/pemeliharaan/perintah-kerja', [
            'Jenis' => 'Korektif', 'Prioritas' => 'Normal', 'MembutuhkanWaktuHenti' => false, 'MembutuhkanPersetujuan' => false,
            ...$data,
        ]);
        $this->konteks();

        return $respons;
    }

    private function buatPerintahKerja(Aset $aset): PerintahKerja
    {
        $this->konteks();

        return app(BuatPerintahKerja::class)->jalankan([
            'Jenis' => 'Korektif', 'Judul' => 'Perbaikan '.$aset->Nama, 'Prioritas' => 'Normal',
            'LokasiId' => $this->ruangIcu->Id, 'AsetIds' => [$aset->Id],
        ], $this->koordinator->Id);
    }

    private function perintahKerjaUntukAset(Aset $aset): PerintahKerja
    {
        $perintahKerjaId = PerintahKerjaAset::query()->where('AsetId', $aset->Id)->value('PerintahKerjaId');

        return PerintahKerja::query()->findOrFail($perintahKerjaId);
    }

    private function buatKeluhan(Aset $aset, ?UnitOrganisasi $unitPengelola): Keluhan
    {
        return Keluhan::create([
            'Nomor' => 'KLH-'.uniqid(),
            'AsetId' => $aset->Id,
            'LokasiId' => $this->ruangIcu->Id,
            'UnitPengelolaId' => $unitPengelola?->Id,
            'Judul' => 'Keluhan '.$aset->Nama,
            'Deskripsi' => 'Tidak berfungsi.',
            'Prioritas' => 'Normal',
            'Status' => 'Diterima',
            'PelaporId' => $this->koordinator->Id,
            'DilaporkanPada' => now(),
        ]);
    }

    private function buatAset(string $nama, ?UnitOrganisasi $unitPengelola): Aset
    {
        return Aset::create([
            'KategoriAsetId' => KategoriAset::create(['Nama' => 'Kategori '.$nama])->Id,
            'LokasiId' => $this->ruangIcu->Id,
            'UnitOrganisasiId' => $this->icu->Id,
            'UnitPengelolaId' => $unitPengelola?->Id,
            'Nama' => $nama,
            'Status' => 'Aktif',
            'Kondisi' => 'Baik',
        ]);
    }

    /** @param list<string> $izin */
    private function buatPengguna(string $nama, array $izin, ?UnitOrganisasi $unit = null, ?Lokasi $lokasi = null): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => app(KonteksOrganisasi::class)->wajibId(),
            'Nama' => $nama,
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran '.$nama]);

        foreach ($izin as $kode) {
            $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Uji']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
        }

        PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'UnitOrganisasiId' => $unit?->Id,
            'LokasiId' => $lokasi?->Id,
        ]);

        return $pengguna;
    }

    private function siapkanNomorDokumen(): void
    {
        NomorDokumen::create(['JenisDokumen' => 'PerintahKerja', 'Awalan' => 'WO', 'FormatNomor' => '{Awalan}-{Nomor:4}', 'ResetPeriode' => 'Tahunan']);
    }

    private function konteks(): void
    {
        app(KonteksOrganisasi::class)->tetapkan($this->organisasi->Id);
    }

    private function propHalaman(TestResponse $respons, string $kunci): mixed
    {
        $halaman = $respons->viewData('page');

        return json_decode(json_encode($halaman['props'][$kunci]), true);
    }
}
