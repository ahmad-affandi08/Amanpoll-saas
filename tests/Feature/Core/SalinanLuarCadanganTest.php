<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Cadangan\BerkasCadangan;
use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Salinan cadangan di luar server (FASE 45).
 *
 * Cadangan yang tinggal di disk yang sama dengan datanya hilang bersama server
 * itu. Yang dijaga di sini: salinan sampai di disk luar dan utuh, salinan lokal
 * hanya dipangkas setelah terbukti ada di luar, disk luar dipangkas menurut
 * retensinya sendiri tanpa memutus rantai arsip berkas, pemulihan dapat
 * mengambil dari luar, dan ketiadaan disk luar tidak pernah diam.
 */
final class SalinanLuarCadanganTest extends TestCase
{
    private string $akarUji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->akarUji = storage_path('app/uji-salinan-luar-'.Str::lower(Str::random(6)));
        File::ensureDirectoryExists($this->akarUji);

        config([
            'filesystems.disks.local.root' => $this->akarUji,
            'amanpoll.cadangan.disk' => 'local',
            'amanpoll.cadangan.folder' => 'cadangan',
            'amanpoll.cadangan.disk_luar' => 'cadangan_luar',
            'amanpoll.cadangan.folder_luar' => 'cadangan',
            'amanpoll.cadangan.verifikasi_checksum' => true,
            'amanpoll.cadangan.retensi_hari' => 14,
            'amanpoll.cadangan.retensi_lokal_hari' => 2,
            'amanpoll.cadangan.retensi_luar_hari' => 30,
        ]);

        Storage::fake('cadangan_luar');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->akarUji);

        parent::tearDown();
    }

    public function test_cadangan_lokal_diunggah_utuh_beserta_checksumnya(): void
    {
        $dump = $this->cadanganLokal('basisdata', 'sql.gz', 1, 'isi dump');
        $arsip = $this->cadanganLokal('berkas', 'tar', 1, 'isi arsip');

        $hasil = $this->layanan()->kirimKeLuar();

        $this->assertTrue($hasil['aktif']);
        $this->assertSame([], $hasil['gagal']);
        $this->assertEqualsCanonicalizing([basename($dump), basename($arsip)], $hasil['terkirim']);

        $luar = Storage::disk('cadangan_luar');
        $this->assertSame('isi dump', $luar->get('cadangan/'.basename($dump)));
        $this->assertSame('isi arsip', $luar->get('cadangan/'.basename($arsip)));
        $this->assertSame(
            hash_file('sha256', $dump).'  '.basename($dump)."\n",
            $luar->get('cadangan/'.basename($dump).'.sha256'),
        );
    }

    public function test_yang_sudah_utuh_di_luar_tidak_diunggah_ulang(): void
    {
        $this->cadanganLokal('basisdata', 'sql.gz', 1, 'isi dump');

        $this->layanan()->kirimKeLuar();

        $this->assertSame([], $this->layanan()->kirimKeLuar()['terkirim']);
    }

    public function test_salinan_lokal_yang_sudah_ada_di_luar_dipangkas_setelah_retensi_lokal(): void
    {
        $lama = $this->cadanganLokal('basisdata', 'sql.gz', 5, 'dump lama');
        $baru = $this->cadanganLokal('basisdata', 'sql.gz', 1, 'dump baru');

        $this->layanan()->kirimKeLuar();
        $dihapus = $this->layanan()->pangkas();

        $this->assertSame(1, $dihapus);
        $this->assertFileDoesNotExist($lama);
        $this->assertFileExists($baru);
        Storage::disk('cadangan_luar')->assertExists('cadangan/'.basename($lama));
    }

    /** Salinan lokal yang belum pernah sampai di luar adalah satu-satunya salinan; ia ditahan sampai retensi panjang. */
    public function test_gagal_unggah_tidak_menghapus_salinan_lokal(): void
    {
        $this->pakaiDiskLuarYangGagalMenulis();
        $lama = $this->cadanganLokal('basisdata', 'sql.gz', 5, 'dump lama');
        $baru = $this->cadanganLokal('basisdata', 'sql.gz', 1, 'dump baru');

        $hasil = $this->layanan()->kirimKeLuar();
        $this->layanan()->pangkas();

        $this->assertArrayHasKey(basename($lama), $hasil['gagal']);
        $this->assertStringContainsString('koneksi putus', $hasil['gagal'][basename($lama)]);
        $this->assertFileExists($lama);
        $this->assertFileExists($baru);
    }

    /** Salinan yang tidak cocok dengan aslinya tidak boleh tertinggal di luar dan dikira utuh. */
    public function test_salinan_luar_yang_tidak_cocok_dihapus_dan_lokal_disimpan(): void
    {
        $palsu = Storage::fake('cadangan_luar');
        Storage::set('cadangan_luar', new class($palsu->getDriver(), $palsu->getAdapter(), $palsu->getConfig()) extends FilesystemAdapter
        {
            public function writeStream($path, $resource, array $options = [])
            {
                return $this->put($path, 'terpotong');
            }
        });
        $dump = $this->cadanganLokal('basisdata', 'sql.gz', 5, 'dump yang lebih panjang');

        $hasil = $this->layanan()->kirimKeLuar();
        $this->layanan()->pangkas();

        $this->assertStringContainsString('tidak cocok', $hasil['gagal'][basename($dump)]);
        $palsu->assertMissing('cadangan/'.basename($dump));
        $palsu->assertMissing('cadangan/'.basename($dump).'.sha256');
        $this->assertFileExists($dump);
    }

    public function test_disk_luar_dipangkas_menurut_retensi_luar(): void
    {
        $luar = Storage::disk('cadangan_luar');
        $lama = $this->namaCadangan('basisdata', 'sql.gz', 40);
        $baru = $this->namaCadangan('basisdata', 'sql.gz', 10);
        foreach ([$lama, $baru] as $nama) {
            $luar->put('cadangan/'.$nama, 'x');
            $luar->put('cadangan/'.$nama.'.sha256', 'x');
        }
        $luar->put('cadangan/catatan-penting.txt', 'bukan cadangan');

        $dihapus = $this->layanan()->pangkasLuar();

        $this->assertSame(1, $dihapus);
        $luar->assertMissing('cadangan/'.$lama);
        $luar->assertMissing('cadangan/'.$lama.'.sha256');
        $luar->assertExists('cadangan/'.$baru);
        $luar->assertExists('cadangan/catatan-penting.txt');
    }

    /**
     * Arsip selisih tanpa arsip penuh dasarnya tidak dapat dipulihkan, dan
     * cadangan basis data terakhir tidak boleh ikut habis walau sudah tua.
     * Selisih bersifat kumulatif, jadi selisih tua yang sudah digantikan
     * selisih lebih baru dari dasar yang sama boleh dibuang.
     */
    public function test_pemangkasan_tidak_memutus_rantai_arsip_berkas(): void
    {
        config(['amanpoll.cadangan.retensi_luar_hari' => 10]);
        $luar = Storage::disk('cadangan_luar');

        $nama = [
            'penuhLama' => $this->namaCadangan('berkas', 'tar', 40),
            'selisihLama' => $this->namaCadangan('berkas-selisih', 'tar', 39),
            'penuhDasar' => $this->namaCadangan('berkas', 'tar', 15),
            'selisihTua' => $this->namaCadangan('berkas-selisih', 'tar', 12),
            'selisihMuda' => $this->namaCadangan('berkas-selisih', 'tar', 5),
            'penuhBaru' => $this->namaCadangan('berkas', 'tar', 2),
            'dumpTerakhir' => $this->namaCadangan('basisdata', 'sql.gz', 60),
        ];
        foreach ($nama as $satu) {
            $luar->put('cadangan/'.$satu, 'x');
        }

        $this->layanan()->pangkasLuar();

        $luar->assertMissing('cadangan/'.$nama['penuhLama']);
        $luar->assertMissing('cadangan/'.$nama['selisihLama']);
        $luar->assertMissing('cadangan/'.$nama['selisihTua']);
        $luar->assertExists('cadangan/'.$nama['penuhDasar']);
        $luar->assertExists('cadangan/'.$nama['selisihMuda']);
        $luar->assertExists('cadangan/'.$nama['penuhBaru']);
        $luar->assertExists('cadangan/'.$nama['dumpTerakhir']);
    }

    /** Pencadangan yang berhenti lama tidak boleh berakhir tanpa rantai arsip berkas terbaru. */
    public function test_rantai_arsip_berkas_terbaru_disimpan_walau_sudah_tua(): void
    {
        config(['amanpoll.cadangan.retensi_luar_hari' => 10]);
        $luar = Storage::disk('cadangan_luar');
        $penuh = $this->namaCadangan('berkas', 'tar', 50);
        $selisih = $this->namaCadangan('berkas-selisih', 'tar', 45);
        $luar->put('cadangan/'.$penuh, 'x');

        $this->assertSame(0, $this->layanan()->pangkasLuar(), 'Arsip penuh satu-satunya ikut terhapus.');

        $luar->put('cadangan/'.$selisih, 'x');

        $this->assertSame(0, $this->layanan()->pangkasLuar(), 'Rantai arsip terbaru ikut terhapus.');
    }

    public function test_tanpa_disk_luar_perintah_memperingatkan_dan_retensi_lokal_tetap(): void
    {
        config(['amanpoll.cadangan.disk_luar' => null]);
        $this->pakaiMysqldumpTiruan();
        Log::spy();
        $lama = $this->cadanganLokal('basisdata', 'sql.gz', 20, 'dump lama');
        $dalamRetensi = $this->cadanganLokal('basisdata', 'sql.gz', 5, 'dump lima hari');

        $this->artisan('cadangan:jalankan', ['--tanpa-berkas' => true])
            ->expectsOutputToContain('PERINGATAN: Disk luar cadangan belum diatur')
            ->assertExitCode(0);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $pesan): bool => str_contains($pesan, 'tidak disalin ke luar server'))
            ->once();
        $this->assertFileDoesNotExist($lama);
        $this->assertFileExists($dalamRetensi, 'Tanpa disk luar, retensi lokal tetap retensi_hari, bukan retensi_lokal_hari.');
        Storage::disk('cadangan_luar')->assertDirectoryEmpty('/');
    }

    public function test_perintah_mengunggah_dump_baru_ke_disk_luar(): void
    {
        $this->pakaiMysqldumpTiruan();

        $this->artisan('cadangan:jalankan', ['--tanpa-berkas' => true])
            ->doesntExpectOutputToContain('PERINGATAN')
            ->assertExitCode(0);

        $dump = app(BerkasCadangan::class)->basisDataTerbaru();
        $this->assertNotNull($dump);
        Storage::disk('cadangan_luar')->assertExists('cadangan/'.basename($dump));
    }

    /** Unggahan yang gagal harus menggagalkan perintah supaya pemantau cron melihatnya. */
    public function test_perintah_gagal_bila_unggah_gagal_dan_dump_lokal_tetap_ada(): void
    {
        $this->pakaiMysqldumpTiruan();
        $this->pakaiDiskLuarYangGagalMenulis();
        Log::spy();

        $this->artisan('cadangan:jalankan', ['--tanpa-berkas' => true])->assertExitCode(1);

        $this->assertNotNull(app(BerkasCadangan::class)->basisDataTerbaru());
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $pesan): bool => str_contains($pesan, 'disk luar gagal'))
            ->once();
    }

    /** Setelah server hilang, pemulihan dimulai dari disk luar. */
    public function test_pemulihan_mengambil_dump_dari_disk_luar(): void
    {
        $tangkapan = $this->pakaiMysqlTiruan();
        $nama = $this->namaCadangan('basisdata', 'sql.gz', 1);
        $isi = (string) gzencode('CREATE TABLE `Uji` (`Id` int);');
        $luar = Storage::disk('cadangan_luar');
        $luar->put('cadangan/'.$nama, $isi);
        $luar->put('cadangan/'.$nama.'.sha256', hash('sha256', $isi).'  '.$nama."\n");

        $this->artisan('cadangan:pulihkan', ['--ke' => 'amanpoll_periksa', '--paksa' => true])->assertExitCode(0);

        $this->assertSame('CREATE TABLE `Uji` (`Id` int);', File::get($tangkapan));
        $this->assertFileExists($this->akarUji.'/cadangan/'.$nama);
    }

    public function test_unduhan_yang_checksumnya_tidak_cocok_ditolak_dan_tidak_ditinggalkan(): void
    {
        $nama = $this->namaCadangan('basisdata', 'sql.gz', 1);
        $luar = Storage::disk('cadangan_luar');
        $luar->put('cadangan/'.$nama, 'isi yang rusak di perjalanan');
        $luar->put('cadangan/'.$nama.'.sha256', hash('sha256', 'isi asli').'  '.$nama."\n");

        try {
            $this->layanan()->ambilDariLuar($nama);
            $this->fail('Unduhan yang tidak cocok seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('tidak cocok', $galat->getMessage());
        }

        $this->assertSame([], File::glob($this->akarUji.'/cadangan/*'));
    }

    /** Dulu AMANPOLL_CADANGAN_DISK=s3 diam-diam tetap menulis ke storage lokal. */
    public function test_disk_kerja_yang_bukan_lokal_ditolak_terang_terangan(): void
    {
        config(['amanpoll.cadangan.disk' => 's3']);

        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('AMANPOLL_CADANGAN_DISK_LUAR');

        app(BerkasCadangan::class)->folder();
    }

    private function layanan(): LayananCadangan
    {
        return app(LayananCadangan::class);
    }

    private function namaCadangan(string $jenis, string $ekstensi, int $hariLalu): string
    {
        return $jenis.'-'.CarbonImmutable::now()->subDays($hariLalu)->format('Ymd-His').'.'.$ekstensi;
    }

    private function cadanganLokal(string $jenis, string $ekstensi, int $hariLalu, string $isi): string
    {
        $jalur = app(BerkasCadangan::class)->jalurBaru($jenis, $ekstensi, CarbonImmutable::now()->subDays($hariLalu));
        File::put($jalur, $isi);

        return $jalur;
    }

    private function pakaiDiskLuarYangGagalMenulis(): void
    {
        $palsu = Storage::fake('cadangan_luar');
        Storage::set('cadangan_luar', new class($palsu->getDriver(), $palsu->getAdapter(), $palsu->getConfig()) extends FilesystemAdapter
        {
            public function writeStream($path, $resource, array $options = [])
            {
                throw new RuntimeException('koneksi putus');
            }
        });
    }

    /** mysqldump tiruan yang mengeluarkan satu definisi tabel, supaya alur perintah teruji tanpa dump sungguhan. */
    private function pakaiMysqldumpTiruan(): void
    {
        $skrip = $this->akarUji.'/mysqldump-tiruan';
        File::put($skrip, "#!/bin/sh\necho 'CREATE TABLE `Uji` (`Id` int);'\n");
        chmod($skrip, 0755);
        config(['amanpoll.cadangan.mysqldump' => $skrip]);
    }

    /** mysql tiruan yang menyimpan masukannya; kembaliannya jalur tangkapan itu. */
    private function pakaiMysqlTiruan(): string
    {
        $skrip = $this->akarUji.'/mysql-tiruan';
        $tangkapan = $this->akarUji.'/tangkapan.sql';
        File::put($skrip, "#!/bin/sh\ncat > ".escapeshellarg($tangkapan)."\n");
        chmod($skrip, 0755);
        config(['amanpoll.cadangan.mysql' => $skrip]);

        return $tangkapan;
    }
}
