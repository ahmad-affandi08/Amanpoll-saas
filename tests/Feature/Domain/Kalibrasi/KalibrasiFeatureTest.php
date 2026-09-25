<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Kalibrasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use App\Domain\Kalibrasi\Application\Actions\KelolaJenisKalibrasi;
use App\Domain\Kalibrasi\Application\Actions\KelolaPelaksanaanKalibrasi;
use App\Domain\Kalibrasi\Application\Actions\KelolaRencanaKalibrasi;
use App\Domain\Kalibrasi\Application\Services\LayananPeringatanKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class KalibrasiFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_14_01_jenis_kalibrasi_crud_dan_titik_ukur_template(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $kelolaJenis = app(KelolaJenisKalibrasi::class);

        // 1. Buat Jenis Kalibrasi
        $jenis = $kelolaJenis->buat([
            'Kode' => 'CAL-SUHU',
            'Nama' => 'Kalibrasi Sensor Suhu RTD & Thermocouple',
            'Deskripsi' => 'Metode perbandingan bak cairan terkontrol, acuan EURAMET cg-13',
            'Aktif' => true,
        ], $pengguna->Id);

        $this->assertSame('CAL-SUHU', $jenis->Kode);
        $this->assertTrue($jenis->Aktif);

        // 2. Tambah template Titik Ukur Standar
        $titik1 = $kelolaJenis->tambahTitikUkur($jenis, [
            'Nama' => 'Titik Es 0 °C',
            'Satuan' => '°C',
            'NilaiReferensi' => 0.00,
            'ToleransiMinus' => 0.20,
            'ToleransiPlus' => 0.20,
            'Urutan' => 1,
        ], $pengguna->Id);

        $titik2 = $kelolaJenis->tambahTitikUkur($jenis, [
            'Nama' => 'Titik Didih 100 °C',
            'Satuan' => '°C',
            'NilaiReferensi' => 100.00,
            'ToleransiMinus' => 0.50,
            'ToleransiPlus' => 0.50,
            'Urutan' => 2,
        ], $pengguna->Id);

        $this->assertSame(2, $jenis->titikUkur()->count());
        $this->assertEquals(0.00, (float) $titik1->NilaiReferensi);
        $this->assertEquals(0.20, (float) $titik1->ToleransiMinus);

        // 3. Perbarui Jenis Kalibrasi
        $jenisDiperbarui = $kelolaJenis->perbarui($jenis, [
            'Nama' => 'Kalibrasi Suhu & Kelembapan Presisi',
        ], $pengguna->Id);

        $this->assertSame('Kalibrasi Suhu & Kelembapan Presisi', $jenisDiperbarui->Nama);
    }

    public function test_14_02_rencana_kalibrasi_siklus_dan_perhitungan_next_due(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $aset = $this->buatAset($organisasi, [
            'KodeAset' => 'AST-TMP-01',
            'Nama' => 'Thermometer Digital Fluke',
        ]);

        $jenis = JenisKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'CAL-FLK',
            'Nama' => 'Kalibrasi Fluke',
            'Aktif' => true,
        ]);

        $kelolaRencana = app(KelolaRencanaKalibrasi::class);

        $tglMulai = '2026-01-01';
        $intervalHari = 180;

        $rencana = $kelolaRencana->buat([
            'AsetId' => $aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'IntervalHari' => $intervalHari,
            'TanggalMulai' => $tglMulai,
            'PeringatanHariSebelum' => 25,
            'Aktif' => true,
        ], $pengguna->Id);

        $this->assertSame($aset->Id, $rencana->AsetId);
        $this->assertSame(180, $rencana->IntervalHari);
        $this->assertSame(25, $rencana->PeringatanHariSebelum);

        // Perhitungan otomatis TanggalBerikutnya = TanggalMulai + 180 hari (2026-06-30)
        $expectedNextDue = Carbon::parse($tglMulai)->addDays($intervalHari)->toDateString();
        $this->assertSame($expectedNextDue, $rencana->TanggalBerikutnya->toDateString());
    }

    public function test_14_03_dan_14_04_pelaksanaan_titik_ukur_pass_fail(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $aset = $this->buatAset($organisasi, [
            'KodeAset' => 'AST-MANO-01',
            'Nama' => 'Pressure Gauge Wika',
        ]);

        $jenis = JenisKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'CAL-PRESS',
            'Nama' => 'Kalibrasi Tekanan',
            'Aktif' => true,
        ]);

        // Tambah 2 template titik ukur
        $kelolaJenis = app(KelolaJenisKalibrasi::class);
        $titik1 = $kelolaJenis->tambahTitikUkur($jenis, [
            'Nama' => 'Tekanan 5 bar',
            'Satuan' => 'bar',
            'NilaiReferensi' => 5.0,
            'ToleransiMinus' => 0.1,
            'ToleransiPlus' => 0.1,
            'Urutan' => 1,
        ], $pengguna->Id);

        $titik2 = $kelolaJenis->tambahTitikUkur($jenis, [
            'Nama' => 'Tekanan 10 bar',
            'Satuan' => 'bar',
            'NilaiReferensi' => 10.0,
            'ToleransiMinus' => 0.1,
            'ToleransiPlus' => 0.1,
            'Urutan' => 2,
        ], $pengguna->Id);

        $kelolaPelaksanaan = app(KelolaPelaksanaanKalibrasi::class);

        // Jadwalkan Pelaksanaan Kalibrasi (template titik ukur disalin otomatis)
        $pelaksanaan = $kelolaPelaksanaan->jadwalkan([
            'AsetId' => $aset->Id,
            'JenisKalibrasiId' => $jenis->Id,
            'TanggalKalibrasi' => '2026-03-15',
            'Laboratorium' => 'Lab Metrologi Tekanan',
            'DilaksanakanOleh' => $pengguna->Id,
        ], $pengguna->Id);

        $this->assertNotNull($pelaksanaan->Nomor);
        // Tanpa pola buatan admin, nomor memakai pola bawaan KAL/{Tahun}/{Nomor:4}.
        $this->assertMatchesRegularExpression('#^KAL/\d{4}/0001$#', $pelaksanaan->Nomor);
        $this->assertSame(2, $pelaksanaan->hasilTitikUkur()->count());

        $hasilItems = $pelaksanaan->hasilTitikUkur()->get();
        $hasilItem1 = $hasilItems->firstWhere('TitikUkurKalibrasiId', $titik1->Id);
        $hasilItem2 = $hasilItems->firstWhere('TitikUkurKalibrasiId', $titik2->Id);

        // Uji Titik 1: NilaiTerukur = 5.05 (Toleransi 4.9 - 5.1) -> Lolos.
        $kelolaPelaksanaan->simpanHasilTitikUkur($pelaksanaan, [
            [
                'Id' => $hasilItem1->Id,
                'TitikUkurKalibrasiId' => $titik1->Id,
                'NamaTitik' => $titik1->Nama,
                'NilaiReferensi' => 5.0,
                'NilaiTerukur' => 5.05,
                'ToleransiMinus' => 0.1,
                'ToleransiPlus' => 0.1,
                'Satuan' => 'bar',
            ],
            [
                'Id' => $hasilItem2->Id,
                'TitikUkurKalibrasiId' => $titik2->Id,
                'NamaTitik' => $titik2->Nama,
                'NilaiReferensi' => 10.0,
                'NilaiTerukur' => 10.30,
                'ToleransiMinus' => 0.1,
                'ToleransiPlus' => 0.1,
                'Satuan' => 'bar',
            ],
        ], $pengguna->Id);

        $hasilItemsSegar = $pelaksanaan->hasilTitikUkur()->get();
        $segar1 = $hasilItemsSegar->firstWhere('TitikUkurKalibrasiId', $titik1->Id);
        $segar2 = $hasilItemsSegar->firstWhere('TitikUkurKalibrasiId', $titik2->Id);

        // Verifikasi hasil evaluasi titik ukur
        $this->assertSame('Lolos', $segar1->Hasil);
        $this->assertEquals(0.05, (float) $segar1->Koreksi);

        $this->assertSame('Gagal', $segar2->Hasil);
        $this->assertEquals(0.30, (float) $segar2->Koreksi);
    }

    public function test_gate_14_finalisasi_sertifikat_konsistensi_next_due(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $aset = $this->buatAset($organisasi, [
            'KodeAset' => 'AST-GATE-14',
            'Nama' => 'Timbangan Analitik Sartorius',
        ]);

        $kelolaRencana = app(KelolaRencanaKalibrasi::class);
        $kelolaPelaksanaan = app(KelolaPelaksanaanKalibrasi::class);

        // Buat Rencana Kalibrasi dengan interval 365 hari
        $rencana = $kelolaRencana->buat([
            'AsetId' => $aset->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2026-01-10',
            'PeringatanHariSebelum' => 30,
        ], $pengguna->Id);

        $this->assertSame('2027-01-10', $rencana->TanggalBerikutnya->toDateString());

        // Buat Pelaksanaan Kalibrasi terhubung ke rencana
        $pelaksanaan = $kelolaPelaksanaan->jadwalkan([
            'AsetId' => $aset->Id,
            'RencanaKalibrasiId' => $rencana->Id,
            'TanggalKalibrasi' => '2026-06-20',
        ], $pengguna->Id);

        // Finalisasi & Otorisasi Sertifikat pada tanggal pelaksanaan 2026-06-20
        $final = $kelolaPelaksanaan->finalisasi($pelaksanaan, [
            'Hasil' => 'Lolos',
            'NomorSertifikat' => 'CERT-ISO17025-2026-001',
            'TanggalKalibrasi' => '2026-06-20',
            'Laboratorium' => 'Balai Kalibrasi Nasional',
        ], $pengguna->Id);

        $this->assertSame('Lolos', $final->Hasil);
        $this->assertSame('CERT-ISO17025-2026-001', $final->NomorSertifikat);
        $this->assertSame($pengguna->Id, $final->DiverifikasiOleh);
        $this->assertNotNull($final->DiverifikasiPada);

        // GATE 14: RencanaKalibrasi.TanggalBerikutnya harus diperbarui otomatis.
        $rencanaSegar = $rencana->fresh();
        $this->assertSame('2027-06-20', $rencanaSegar->TanggalBerikutnya->toDateString());
        $this->assertSame('2027-06-20', $final->TanggalBerlakuSampai->toDateString());
    }

    /**
     * Alat yang gagal kalibrasi tidak boleh tampil valid. Dulu hasil Gagal ikut memajukan
     * TanggalBerikutnya satu interval seperti Lolos, sehingga dasbor menandainya "Valid".
     */
    public function test_finalisasi_gagal_tidak_memajukan_jatuh_tempo_dan_tanpa_masa_berlaku(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $aset = $this->buatAset($organisasi, ['KodeAset' => 'AST-GAGAL-01', 'Nama' => 'Pressure Gauge Master']);
        $kelolaPelaksanaan = app(KelolaPelaksanaanKalibrasi::class);
        $rencana = app(KelolaRencanaKalibrasi::class)->buat([
            'AsetId' => $aset->Id,
            'IntervalHari' => 180,
            'TanggalMulai' => '2026-01-10',
            'PeringatanHariSebelum' => 30,
        ], $pengguna->Id);

        $pelaksanaan = $kelolaPelaksanaan->jadwalkan([
            'AsetId' => $aset->Id,
            'RencanaKalibrasiId' => $rencana->Id,
            'TanggalKalibrasi' => '2026-06-20',
        ], $pengguna->Id);

        $final = $kelolaPelaksanaan->finalisasi($pelaksanaan, [
            'Hasil' => 'Gagal',
            'NomorSertifikat' => 'CERT-GAGAL-001',
            'TanggalKalibrasi' => '2026-06-20',
            'TanggalBerlakuSampai' => '2026-12-20',
        ], $pengguna->Id);

        $this->assertSame('Gagal', $final->Hasil);
        $this->assertNull($final->TanggalBerlakuSampai, 'Sertifikat gagal tidak punya masa berlaku.');
        $this->assertSame('2026-06-20', $rencana->fresh()->TanggalBerikutnya->toDateString(), 'Perlu kalibrasi ulang sejak tanggal gagal.');

        // Kalibrasi ulang yang lolos kembali memajukan jadwal satu interval.
        $ulang = $kelolaPelaksanaan->jadwalkan([
            'AsetId' => $aset->Id,
            'RencanaKalibrasiId' => $rencana->Id,
            'TanggalKalibrasi' => '2026-06-27',
        ], $pengguna->Id);
        $kelolaPelaksanaan->finalisasi($ulang, ['Hasil' => 'Lolos', 'TanggalKalibrasi' => '2026-06-27'], $pengguna->Id);

        $this->assertSame('2026-12-24', $rencana->fresh()->TanggalBerikutnya->toDateString());
    }

    public function test_14_05_reminder_due_soon_overdue_dan_anti_duplikasi(): void
    {
        Carbon::setTestNow('2026-09-20');

        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $aset1 = $this->buatAset($organisasi, ['KodeAset' => 'AST-REM-01', 'Nama' => 'Aset Jatuh Tempo Segera']);
        $aset2 = $this->buatAset($organisasi, ['KodeAset' => 'AST-REM-02', 'Nama' => 'Aset Sudah Terlambat']);
        $aset3 = $this->buatAset($organisasi, ['KodeAset' => 'AST-REM-03', 'Nama' => 'Aset Masih Aman']);

        // Rencana 1: Jatuh tempo dalam 10 hari (2026-09-30) dengan batas peringatan 30 hari -> SegeraJatuhTempo
        RencanaKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'AsetId' => $aset1->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2025-09-30',
            'TanggalBerikutnya' => '2026-09-30',
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);

        // Rencana 2: Jatuh tempo lima hari lalu (2026-09-15) -> Terlambat
        RencanaKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'AsetId' => $aset2->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2025-09-15',
            'TanggalBerikutnya' => '2026-09-15',
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);

        // Rencana 3: Jatuh tempo tahun depan (2027-05-01) -> Valid
        RencanaKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'AsetId' => $aset3->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2026-05-01',
            'TanggalBerikutnya' => '2027-05-01',
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);

        $layananPeringatan = app(LayananPeringatanKalibrasi::class);

        // 1. Uji Statistik Kepatuhan Dashboard
        $stat = $layananPeringatan->hitungKepatuhan($organisasi->Id);
        $this->assertSame(3, $stat['total']);
        $this->assertSame(1, $stat['valid']);
        $this->assertSame(1, $stat['segeraJatuhTempo']);
        $this->assertSame(1, $stat['terlambat']);
        $this->assertEquals(33.3, $stat['persentaseKepatuhan']);

        // 2. Kirim Notifikasi Pertama Kali Hari Ini
        $hasilPertama = $layananPeringatan->kirimPeringatan($organisasi->Id);
        $this->assertSame(1, $hasilPertama['segeraJatuhTempo']);
        $this->assertSame(1, $hasilPertama['terlambat']);
        $this->assertSame(0, $hasilPertama['dilewati']);

        // Isi pesan menyebut jumlah hari sebagai bilangan bulat positif, bukan "-5" atau "10.0".
        $this->assertSame(
            ['Kalibrasi untuk aset Aset Sudah Terlambat (AST-REM-02) telah terlambat 5 hari (jatuh tempo: 15/09/2026).'],
            $this->isiNotifikasi($organisasi, 'Kalibrasi.Terlambat'),
        );
        $this->assertSame(
            ['Kalibrasi untuk aset Aset Jatuh Tempo Segera (AST-REM-01) akan jatuh tempo dalam 10 hari (30/09/2026).'],
            $this->isiNotifikasi($organisasi, 'Kalibrasi.SegeraJatuhTempo'),
        );

        // 3. UJI ANTI DUPLIKASI (14.05): Menjalankan pengingat kedua kali pada hari yang sama
        $hasilKedua = $layananPeringatan->kirimPeringatan($organisasi->Id);
        $this->assertSame(0, $hasilKedua['segeraJatuhTempo']);
        $this->assertSame(0, $hasilKedua['terlambat']);
        $this->assertSame(2, $hasilKedua['dilewati']); // Kedua reminder dilewati karena sudah dikirim hari ini
    }

    public function test_kalibrasi_controller_endpoints(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $pengguna = $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);

        $this->actingAs($pengguna);

        // 1. Dashboard Index
        $resDashboard = $this->get(route('kalibrasi.index'));
        $resDashboard->assertOk();

        // 2. Jenis Kalibrasi Index
        $resJenis = $this->get(route('kalibrasi.jenis.index'));
        $resJenis->assertOk();

        // 3. Rencana Kalibrasi Index
        $resRencana = $this->get(route('kalibrasi.rencana.index'));
        $resRencana->assertOk();

        // 4. Pelaksanaan Kalibrasi Index
        $resPelaksanaan = $this->get(route('kalibrasi.pelaksanaan.index'));
        $resPelaksanaan->assertOk();
    }

    private function buatPengguna(Organisasi $organisasi, array $izin = []): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi Kalibrasi '.uniqid(),
            'Email' => 'teknisi.'.uniqid().'@amanpoll.test',
            'KataSandi' => bcrypt('password'),
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
                $modelIzin = Izin::firstOrCreate(['Kode' => $kode], ['Nama' => $kode, 'Modul' => 'Kalibrasi']);
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

    private function tetapkanKonteks(Organisasi $organisasi): void
    {
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
    }

    /**
     * Isi notifikasi yang berbeda untuk satu jenis peristiwa; satu pesan bisa
     * tertulis di beberapa kanal, jadi duplikatnya dirapatkan.
     *
     * @return list<string>
     */
    private function isiNotifikasi(Organisasi $organisasi, string $jenisPeristiwa): array
    {
        return DB::table('Notifikasi')
            ->where('OrganisasiId', $organisasi->Id)
            ->where('JenisPeristiwa', $jenisPeristiwa)
            ->distinct()
            ->pluck('Isi')
            ->map(fn (mixed $isi): string => (string) $isi)
            ->values()
            ->all();
    }

    private function buatAset(Organisasi $organisasi, array $atribut = []): Aset
    {
        $kategori = KategoriAset::create([
            'OrganisasiId' => $organisasi->Id,
            'Kode' => 'KAT-'.uniqid(),
            'Nama' => 'Kategori '.uniqid(),
        ]);

        return Aset::create(array_merge([
            'OrganisasiId' => $organisasi->Id,
            'KategoriAsetId' => $kategori->Id,
            'KodeAset' => 'AST-'.uniqid(),
            'Nama' => 'Aset Uji',
            'Status' => StatusAset::Aktif->value,
        ], $atribut));
    }

    /**
     * Pengingat kalibrasi memakai hari rumah sakit, termasuk pencegah duplikatnya.
     *
     * Pukul 01:00 WIB tanggal 22 (18:00 UTC tanggal 21) alat bertanggal 21
     * sudah terlambat. Pengingat yang dikirim saat itu tersimpan bertanggal
     * UTC 21, jadi pencegah duplikat yang membandingkan tanggal UTC mengirim
     * ulang pengingat yang sama pukul 10:00 WIB di hari yang sama.
     */
    public function test_pengingat_kalibrasi_dan_pencegah_duplikatnya_memakai_hari_rumah_sakit(): void
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-CAL-'.uniqid(), 'Nama' => 'Organisasi Kalibrasi']);
        $this->buatPengguna($organisasi, ['Kalibrasi.Kelola']);
        $this->tetapkanKonteks($organisasi);
        RencanaKalibrasi::create([
            'OrganisasiId' => $organisasi->Id,
            'AsetId' => $this->buatAset($organisasi, ['KodeAset' => 'AST-DINI-01'])->Id,
            'IntervalHari' => 365,
            'TanggalMulai' => '2025-09-21',
            'TanggalBerikutnya' => '2026-09-21',
            'PeringatanHariSebelum' => 30,
            'Aktif' => true,
        ]);
        $layananPeringatan = app(LayananPeringatanKalibrasi::class);

        Carbon::setTestNow(CarbonImmutable::parse('2026-09-21 18:00:00', 'UTC'));
        $this->assertSame(1, $layananPeringatan->kirimPeringatan($organisasi->Id)['terlambat']);

        Carbon::setTestNow(CarbonImmutable::parse('2026-09-22 03:00:00', 'UTC'));
        $kedua = $layananPeringatan->kirimPeringatan($organisasi->Id);
        $this->assertSame(0, $kedua['terlambat']);
        $this->assertSame(1, $kedua['dilewati']);
    }
}
