<?php

declare(strict_types=1);

namespace Tests\Feature\Pemeliharaan;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Domain\PreventifInspeksi\Application\Actions\JadwalkanPemeliharaanPreventif;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaButirDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaInspeksi;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaPelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaRencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Application\Actions\KelolaTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class PreventifInspeksiTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_gate_13_templat_dan_butir_daftar_periksa_crud_dan_versi_baru(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CK-'.uniqid(), 'Nama' => 'Organisasi Checklist']);
        $pengguna = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);
        $kelolaButir = app(KelolaButirDaftarPeriksa::class);

        // 1. Buat Templat
        $templat = $kelolaTemplat->buat([
            'Kode' => 'CK-POMPA-01',
            'Nama' => 'Checklist Pompa Sentrifugal',
            'Jenis' => 'Pemeliharaan',
        ], $pengguna->Id);

        $this->assertSame('CK-POMPA-01', $templat->Kode);
        $this->assertSame(1, $templat->VersiTemplat);

        // 2. Tambah Butir Pertanyaan dengan tipe bervariasi
        $butir1 = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Tekanan discharge pompa',
            'Kode' => 'P1',
            'TipeJawaban' => 'Angka',
            'Satuan' => 'bar',
            'NilaiMinimum' => 5.0,
            'NilaiMaksimum' => 10.0,
            'Wajib' => true,
        ]);

        $butir2 = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Apakah terdapat kebocoran pada mechanical seal?',
            'Kode' => 'P2',
            'TipeJawaban' => 'YaTidak',
            'Wajib' => true,
            'MemicuTemuanJika' => ['nilai' => true],
        ]);

        $butir3 = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Kondisi pelumasan bearing',
            'Kode' => 'P3',
            'TipeJawaban' => 'Pilihan',
            'Pilihan' => ['Cukup', 'Kurang', 'Terkontaminasi'],
            'Wajib' => false,
        ]);

        $this->assertCount(3, $templat->fresh()->butir);

        // 3. Urutkan ulang
        $kelolaButir->urutkanUlang($templat, [$butir3->Id, $butir1->Id, $butir2->Id]);
        $this->assertSame(1, $butir3->fresh()->Urutan);
        $this->assertSame(2, $butir1->fresh()->Urutan);

        // 4. Buat Versi Baru
        $templatV2 = $kelolaTemplat->buatVersiBaru($templat, $pengguna->Id);
        $this->assertSame(2, $templatV2->VersiTemplat);
        $this->assertSame('CK-POMPA-01-v2', $templatV2->Kode);
        $this->assertCount(3, $templatV2->butir);
    }

    public function test_gate_13_pelaksanaan_daftar_periksa_validasi_skor_dan_terkunci(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 09:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-PL-'.uniqid(), 'Nama' => 'Organisasi Pelaksanaan']);
        $teknisi = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $kategoriAset = $this->buatKategoriAset($organisasi);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-PMP-'.uniqid(),
            'Nama' => 'Pompa Sirkulasi',
            'Status' => StatusAset::Aktif->value,
        ]);

        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);
        $kelolaButir = app(KelolaButirDaftarPeriksa::class);
        $kelolaPelaksanaan = app(KelolaPelaksanaanDaftarPeriksa::class);

        $templat = $kelolaTemplat->buat([
            'Kode' => 'CK-AC-01',
            'Nama' => 'Pemeriksaan Rutin AC',
        ], $teknisi->Id);

        $butirAngka = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Tekanan Gas Refrigeran',
            'TipeJawaban' => 'Angka',
            'Satuan' => 'psi',
            'NilaiMinimum' => 60,
            'NilaiMaksimum' => 80,
            'Wajib' => true,
        ]);

        $butirYaTidak = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Filter udara bersih dan bebas debu?',
            'TipeJawaban' => 'YaTidak',
            'Wajib' => true,
            'MemicuTemuanJika' => ['nilai' => false],
        ]);

        // Mulai Pelaksanaan
        $pelaksanaan = $kelolaPelaksanaan->mulai([
            'TemplatDaftarPeriksaId' => $templat->Id,
            'AsetId' => $aset->Id,
        ], $teknisi->Id);

        $this->assertSame('Draft', $pelaksanaan->Status);
        $this->assertCount(2, $pelaksanaan->jawaban);

        // Isi jawaban butir 1 dengan nilai abnormal (misal 95 psi, out of range 60-80)
        $kelolaPelaksanaan->simpanJawaban($pelaksanaan, [
            [
                'ButirTemplatDaftarPeriksaId' => $butirAngka->Id,
                'NilaiAngka' => 95,
            ],
        ], $teknisi->Id);

        $jawaban1 = JawabanDaftarPeriksa::query()
            ->where('PelaksanaanDaftarPeriksaId', $pelaksanaan->Id)
            ->where('ButirTemplatDaftarPeriksaId', $butirAngka->Id)
            ->firstOrFail();

        $this->assertFalse($jawaban1->Sesuai, 'Nilai 95 di luar rentang 60-80 harus ditandai tidak sesuai.');

        // Coba finalisasi saat butir kedua (wajib) belum diisi -> harus throw AturanBisnisDilanggar
        $this->expectException(AturanBisnisDilanggar::class);
        $kelolaPelaksanaan->finalisasi($pelaksanaan, null, $teknisi->Id);
    }

    public function test_gate_13_pelaksanaan_finalisasi_sukses_dan_terkunci(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 09:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-PL2-'.uniqid(), 'Nama' => 'Organisasi Pelaksanaan 2']);
        $teknisi = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);
        $kelolaButir = app(KelolaButirDaftarPeriksa::class);
        $kelolaPelaksanaan = app(KelolaPelaksanaanDaftarPeriksa::class);

        $templat = $kelolaTemplat->buat([
            'Kode' => 'CK-GEN-01',
            'Nama' => 'Pemeriksaan Genset',
        ], $teknisi->Id);

        $butir1 = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Level Oli Mesin Normal?',
            'TipeJawaban' => 'YaTidak',
            'Wajib' => true,
        ]);

        $butir2 = $kelolaButir->simpan($templat, [
            'Pertanyaan' => 'Tegangan Baterai Starter',
            'TipeJawaban' => 'Angka',
            'NilaiMinimum' => 12.0,
            'NilaiMaksimum' => 14.5,
            'Wajib' => true,
        ]);

        $pelaksanaan = $kelolaPelaksanaan->mulai([
            'TemplatDaftarPeriksaId' => $templat->Id,
        ], $teknisi->Id);

        // Isi semua jawaban dengan nilai yang sesuai
        $kelolaPelaksanaan->simpanJawaban($pelaksanaan, [
            [
                'ButirTemplatDaftarPeriksaId' => $butir1->Id,
                'NilaiBoolean' => true,
            ],
            [
                'ButirTemplatDaftarPeriksaId' => $butir2->Id,
                'NilaiAngka' => 12.8,
            ],
        ], $teknisi->Id);

        // Finalisasi
        $selesai = $kelolaPelaksanaan->finalisasi($pelaksanaan, 'Kondisi genset prima.', $teknisi->Id);

        $this->assertSame('Selesai', $selesai->Status);
        $this->assertEquals(100.0, (float) $selesai->Skor);
        $this->assertNotNull($selesai->SelesaiPada);

        // Verifikasi locking: mencoba mengubah jawaban setelah selesai harus ditolak
        $this->expectException(AturanBisnisDilanggar::class);
        $kelolaPelaksanaan->simpanJawaban($selesai, [
            [
                'ButirTemplatDaftarPeriksaId' => $butir1->Id,
                'NilaiBoolean' => false,
            ],
        ], $teknisi->Id);
    }

    public function test_gate_13_rencana_pemeliharaan_preventif_dan_penjadwalan_idempoten(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 08:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-PM-'.uniqid(), 'Nama' => 'Organisasi Preventif']);
        $manajer = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
        $this->tetapkanKonteks($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $lokasi = Lokasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'LOK-'.uniqid(),
            'Nama' => 'Ruang Mesin',
            'Status' => 'Aktif',
        ]);

        $kategoriAset = $this->buatKategoriAset($organisasi);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-GEN-01',
            'Nama' => 'Genset Utama',
            'Status' => StatusAset::Aktif->value,
            'LokasiId' => $lokasi->Id,
        ]);

        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);
        $templat = $kelolaTemplat->buat([
            'Kode' => 'CK-PREV-GEN',
            'Nama' => 'Checklist Preventif Genset',
        ], $manajer->Id);

        $kelolaRencana = app(KelolaRencanaPemeliharaan::class);
        $rencana = $kelolaRencana->buat([
            'Kode' => 'PM-GEN-BULANAN',
            'Nama' => 'Preventif Bulanan Genset',
            'IntervalNilai' => 1,
            'IntervalSatuan' => 'Bulan',
            'BuatPerintahKerjaHariSebelum' => 7,
            'TemplatDaftarPeriksaId' => $templat->Id,
            'Prioritas' => 'Tinggi',
        ], $manajer->Id);

        // Tetapkan Aset dengan TanggalMulai hari ini (2026-03-20) dan TanggalBerikutnya jatuh tempo dalam 3 hari (2026-03-23)
        $asetPlan = $kelolaRencana->tetapkanAset(
            $rencana,
            $aset->Id,
            '2026-03-20',
            '2026-03-23'
        );

        $this->assertSame('2026-03-23', $asetPlan->TanggalBerikutnya->toDateString());

        // Jalankan Penjadwalan Preventif pertama kali
        $penjadwal = app(JadwalkanPemeliharaanPreventif::class);
        $hasilPertama = $penjadwal->jalankan(
            tanggalAcuan: CarbonImmutable::parse('2026-03-20'),
            organisasiId: $organisasi->Id,
            penggunaId: $manajer->Id
        );

        $this->assertSame(1, $hasilPertama['jadwalDibuat']);
        $this->assertSame(1, $hasilPertama['perintahKerjaDibuat']);

        // Verifikasi Perintah Kerja Preventif & Pelaksanaan Checklist terbentuk
        $jadwal = JadwalPemeliharaan::query()
            ->where('RencanaPemeliharaanAsetId', $asetPlan->Id)
            ->firstOrFail();

        $this->assertNotNull($jadwal->PerintahKerjaId);
        $perintahKerja = PerintahKerja::findOrFail($jadwal->PerintahKerjaId);
        $this->assertSame('Preventif', $perintahKerja->Jenis);
        $this->assertSame('Tinggi', $perintahKerja->Prioritas);

        $pelaksanaan = PelaksanaanDaftarPeriksa::query()
            ->where('PerintahKerjaId', $perintahKerja->Id)
            ->firstOrFail();
        $this->assertSame($templat->Id, $pelaksanaan->TemplatDaftarPeriksaId);

        // Verifikasi TanggalBerikutnya pada RencanaPemeliharaanAset sudah dimajukan 1 bulan (menjadi 2026-04-23)
        $this->assertSame('2026-04-23', $asetPlan->fresh()->TanggalBerikutnya->toDateString());

        // GATE 13 PERSYARATAN KRUSIAL.
        $this->artisan('pemeliharaan:jadwalkan-preventif', [
            '--organisasi' => $organisasi->Id,
            '--tanggal' => '2026-03-20',
        ])->assertSuccessful();

        $totalJadwal = JadwalPemeliharaan::query()
            ->where('RencanaPemeliharaanAsetId', $asetPlan->Id)
            ->count();
        $this->assertSame(1, $totalJadwal, 'Jadwal pemeliharaan tidak boleh terduplikasi!');

        $totalPK = PerintahKerja::query()
            ->where('OrganisasiId', $organisasi->Id)
            ->where('Jenis', 'Preventif')
            ->count();
        $this->assertSame(1, $totalPK, 'Perintah kerja preventif tidak boleh terduplikasi!');
    }

    public function test_gate_13_inspeksi_aset_catat_temuan_dan_buat_perintah_kerja_korektif(): void
    {
        CarbonImmutable::setTestNow('2026-03-20 10:00:00');

        $organisasi = Organisasi::create(['Kode' => 'ORG-INSP-'.uniqid(), 'Nama' => 'Organisasi Inspeksi']);
        $inspektor = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
        $this->tetapkanKonteks($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $kategoriAset = KategoriAset::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Peralatan Listrik',
        ]);

        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-TRF-01',
            'Nama' => 'Trafo Distribusi Utama',
            'Status' => StatusAset::Aktif->value,
        ]);

        $kelolaInspeksi = app(KelolaInspeksi::class);
        $kelolaTemplat = app(KelolaTemplatDaftarPeriksa::class);

        $templatChecklist = $kelolaTemplat->buat([
            'Kode' => 'CK-INSP-TRF',
            'Nama' => 'Checklist Inspeksi Trafo',
        ], $inspektor->Id);

        // 1. Buat Templat Inspeksi
        $templatInspeksi = $kelolaInspeksi->buatTemplat([
            'Kode' => 'INSP-TRF-01',
            'Nama' => 'Inspeksi Visual Trafo',
            'KategoriAsetId' => $kategoriAset->Id,
            'TemplatDaftarPeriksaId' => $templatChecklist->Id,
            'IntervalHari' => 14,
        ], $inspektor->Id);

        // 2. Jadwalkan Inspeksi
        $inspeksi = $kelolaInspeksi->jadwalkan([
            'TemplatInspeksiId' => $templatInspeksi->Id,
            'AsetId' => $aset->Id,
            'DijadwalkanPada' => '2026-03-20 10:00:00',
            'DilaksanakanOleh' => $inspektor->Id,
        ], $inspektor->Id);

        $this->assertSame('Terjadwal', $inspeksi->Status);
        $this->assertNotEmpty($inspeksi->Nomor);

        // 3. Catat Pelaksanaan dengan Temuan Gagal
        $kelolaInspeksi->laksanakan($inspeksi, [
            'Hasil' => 'Gagal',
            'Temuan' => 'Suhu bushing trafo mencapai 95°C dan tercium bau isolasi terbakar.',
            'TindakLanjut' => 'Perlu de-energize dan penggantian bushing segera.',
        ], $inspektor->Id);

        $inspeksi->refresh();
        $this->assertSame('Selesai', $inspeksi->Status);
        $this->assertSame('Gagal', $inspeksi->Hasil);

        // 4. Buat Perintah Kerja Korektif dari Temuan Inspeksi
        $perintahKerja = $kelolaInspeksi->buatPerintahKerjaKorektif($inspeksi, [
            'Judul' => 'Perbaikan Darurat Bushing Trafo Utama',
            'Prioritas' => 'Darurat',
        ], $inspektor->Id);

        $this->assertSame('Korektif', $perintahKerja->Jenis);
        $this->assertSame('Darurat', $perintahKerja->Prioritas);
        $this->assertSame($perintahKerja->Id, $inspeksi->fresh()->PerintahKerjaId);

        // 5. Coba buat perintah kerja kedua kalinya -> harus dicegah (idempoten / single WO per inspeksi)
        $this->expectException(AturanBisnisDilanggar::class);
        $kelolaInspeksi->buatPerintahKerjaKorektif($inspeksi, [], $inspektor->Id);
    }

    public function test_gate_13_http_controller_web_flows(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-WEB-'.uniqid(), 'Nama' => 'Organisasi Web']);
        $pengguna = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
        $this->tetapkanKonteks($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $kategoriAset = $this->buatKategoriAset($organisasi);
        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategoriAset->Id,
            'KodeAset' => 'AST-WEB-01',
            'Nama' => 'Motor Listrik Web',
            'Status' => StatusAset::Aktif->value,
        ]);

        // 1. GET Templat Index
        $this->actingAs($pengguna)
            ->get('/preventif-inspeksi/templat-daftar-periksa')
            ->assertOk();

        // 2. POST Buat Templat
        $res = $this->actingAs($pengguna)
            ->post('/preventif-inspeksi/templat-daftar-periksa', [
                'Kode' => 'CK-WEB-01',
                'Nama' => 'Checklist Web Test',
                'Jenis' => 'Pemeliharaan',
            ]);
        $res->assertSessionDoesntHaveErrors();
        $res->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $templat = TemplatDaftarPeriksa::where('Kode', 'CK-WEB-01')->firstOrFail();

        // 3. POST Tambah Butir
        $this->actingAs($pengguna)
            ->post("/preventif-inspeksi/templat-daftar-periksa/{$templat->Id}/butir", [
                'Pertanyaan' => 'Apakah motor berputar normal?',
                'TipeJawaban' => 'YaTidak',
                'Wajib' => true,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $butir = ButirTemplatDaftarPeriksa::where('TemplatDaftarPeriksaId', $templat->Id)->firstOrFail();

        // 4. POST Mulai Pelaksanaan
        $this->actingAs($pengguna)
            ->post('/preventif-inspeksi/pelaksanaan-daftar-periksa', [
                'TemplatDaftarPeriksaId' => $templat->Id,
                'AsetId' => $aset->Id,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $pelaksanaan = PelaksanaanDaftarPeriksa::where('TemplatDaftarPeriksaId', $templat->Id)->firstOrFail();

        // 5. PUT Simpan Jawaban
        $this->actingAs($pengguna)
            ->put("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$pelaksanaan->Id}/jawaban", [
                'jawaban' => [
                    [
                        'ButirTemplatDaftarPeriksaId' => $butir->Id,
                        'NilaiBoolean' => true,
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        // 6. POST Finalisasi
        $this->actingAs($pengguna)
            ->post("/preventif-inspeksi/pelaksanaan-daftar-periksa/{$pelaksanaan->Id}/finalisasi", [
                'catatan' => 'Selesai tanpa kendala.',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $this->assertSame('Selesai', $pelaksanaan->fresh()->Status);

        // 7. Rencana Pemeliharaan Web Flows
        $this->actingAs($pengguna)
            ->get('/preventif-inspeksi/rencana-pemeliharaan')
            ->assertOk();

        $this->actingAs($pengguna)
            ->post('/preventif-inspeksi/rencana-pemeliharaan', [
                'Kode' => 'PM-WEB-01',
                'Nama' => 'Rencana Pemeliharaan Web',
                'IntervalNilai' => 14,
                'IntervalSatuan' => 'Hari',
                'Prioritas' => 'Normal',
                'BuatPerintahKerjaHariSebelum' => 3,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $rencana = RencanaPemeliharaan::where('Kode', 'PM-WEB-01')->firstOrFail();

        $this->actingAs($pengguna)
            ->post("/preventif-inspeksi/rencana-pemeliharaan/{$rencana->Id}/aset", [
                'AsetId' => $aset->Id,
                'TanggalMulai' => '2026-03-20',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->tetapkanKonteks($organisasi);
        $this->assertTrue(RencanaPemeliharaanAset::where('RencanaPemeliharaanId', $rencana->Id)->exists());

        // 8. Inspeksi Web Flows
        // Halaman membaca `inspeksi.meta` untuk KontrolPaginasi; tanpa itu ia jatuh begitu ada satu baris.
        $this->actingAs($pengguna)
            ->get('/preventif-inspeksi/inspeksi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman->where('inspeksi.meta.last_page', 1)->etc());
    }

    /** @param list<string> $izin */
    private function buatPengguna(Organisasi $organisasi, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($izin !== []) {
            $this->tetapkanKonteks($organisasi);
            $peran = Peran::create([
                'OrganisasiId' => $organisasi->Id,
                'Kode' => 'PERAN-'.uniqid(),
                'Nama' => 'Peran Uji',
            ]);
            foreach ($izin as $kode) {
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Pemeliharaan']);
                PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $modelIzin->Id]);
            }
            PenggunaPeran::create([
                'OrganisasiId' => $organisasi->Id,
                'PenggunaId' => $pengguna->Id,
                'PeranId' => $peran->Id,
            ]);
        }

        return $pengguna;
    }

    private function siapkanNomorDokumen(Organisasi $organisasi): void
    {
        $this->tetapkanKonteks($organisasi);
        NomorDokumen::firstOrCreate([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'PerintahKerja',
        ], [
            'Awalan' => 'WO',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);

        NomorDokumen::firstOrCreate([
            'OrganisasiId' => $organisasi->Id,
            'JenisDokumen' => 'Inspeksi',
        ], [
            'Awalan' => 'INSP',
            'FormatNomor' => '{Awalan}-{Tahun}-{Nomor:4}',
            'ResetPeriode' => 'Tahunan',
        ]);
    }

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }

    private function buatKategoriAset(Organisasi $organisasi): KategoriAset
    {
        return KategoriAset::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Kategori '.uniqid(),
        ]);
    }

    /**
     * Horizon penjadwalan dihitung dari hari ini rumah sakitnya.
     *
     * Pukul 18:30 UTC tanggal 21 sudah 03:30 WIT tanggal 22 di Jayapura.
     * Rencana jatuh tempo tanggal 29 sudah masuk horizon 7 hari (22 + 7),
     * sedangkan menurut tanggal UTC (21 + 7 = 28) belum. Perintah kerjanya
     * dijadwalkan mulai 00:00 WIT, bukan tengah malam UTC.
     */
    public function test_penjadwalan_preventif_memakai_hari_ini_dan_awal_hari_di_zona_organisasi(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-21 18:30:00', 'UTC'));

        $organisasi = Organisasi::create(['Kode' => 'ORG-PM-'.uniqid(), 'Nama' => 'RS Jayapura', 'ZonaWaktu' => 'Asia/Jayapura']);
        $manajer = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola', 'PerintahKerja.Kelola']);
        $this->tetapkanKonteks($organisasi);
        $this->siapkanNomorDokumen($organisasi);

        $aset = Aset::create([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $this->buatKategoriAset($organisasi)->Id,
            'KodeAset' => 'AST-WIT-01',
            'Nama' => 'Ventilator',
            'Status' => StatusAset::Aktif->value,
        ]);
        $kelolaRencana = app(KelolaRencanaPemeliharaan::class);
        $rencana = $kelolaRencana->buat([
            'Kode' => 'PM-WIT',
            'Nama' => 'Preventif Ventilator',
            'IntervalNilai' => 1,
            'IntervalSatuan' => 'Bulan',
            'BuatPerintahKerjaHariSebelum' => 7,
            'Prioritas' => 'Normal',
        ], $manajer->Id);
        $kelolaRencana->tetapkanAset($rencana, $aset->Id, '2026-08-29', '2026-09-29');

        $hasil = app(JadwalkanPemeliharaanPreventif::class)->jalankan(organisasiId: $organisasi->Id, penggunaId: $manajer->Id);

        $this->assertSame(1, $hasil['jadwalDibuat']);
        $perintahKerja = PerintahKerja::query()->where('OrganisasiId', $organisasi->Id)->sole();
        $this->assertSame('2026-09-28 15:00:00', $perintahKerja->DijadwalkanMulaiPada?->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Halaman pelaksanaan membaca relasi dengan nama yang benar-benar dikirim.
     *
     * Model dikirim mentah ke Inertia, jadi relasinya berkunci snake_case
     * (`templat_daftar_periksa`). Halaman sempat membaca `templatDaftarPeriksa`,
     * sehingga judul templat dan SELURUH butir daftar periksa tidak tampil --
     * teknisi membuka checklist yang kosong. Test ini mengunci kunci props yang
     * dibaca halamannya.
     */
    public function test_halaman_pelaksanaan_menerima_templat_dan_butir_dengan_kunci_yang_dibaca_halaman(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PL3-'.uniqid(), 'Nama' => 'Organisasi Pelaksanaan 3']);
        $teknisi = $this->buatPengguna($organisasi, ['Pemeliharaan.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $templat = app(KelolaTemplatDaftarPeriksa::class)->buat(['Kode' => 'CK-KUNCI', 'Nama' => 'Pemeriksaan Ventilator'], $teknisi->Id);
        app(KelolaButirDaftarPeriksa::class)->simpan($templat, ['Pertanyaan' => 'Alarm tekanan berfungsi?', 'TipeJawaban' => 'YaTidak', 'Wajib' => true]);
        $pelaksanaan = app(KelolaPelaksanaanDaftarPeriksa::class)->mulai(['TemplatDaftarPeriksaId' => $templat->Id], $teknisi->Id);

        $this->actingAs($teknisi)
            ->get(route('preventifInspeksi.pelaksanaan-daftar-periksa.show', $pelaksanaan->Id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('DaftarPeriksa/Pelaksanaan/Show')
                ->where('pelaksanaan.templat_daftar_periksa.Nama', 'Pemeriksaan Ventilator')
                ->where('pelaksanaan.templat_daftar_periksa.butir.0.Pertanyaan', 'Alarm tekanan berfungsi?')
                ->has('pelaksanaan.dilaksanakan_oleh'));
    }
}
