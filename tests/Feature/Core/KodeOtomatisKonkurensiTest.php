<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Penomoran\Services\LayananKodeOtomatis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Membuktikan penghitung kode aman dari race condition memakai proses OS sungguhan.
 *
 * Penjaga yang diuji adalah `lockForUpdate()`, dan test biasa tidak bisa
 * menyentuhnya: membuangnya tetap meluluskan seluruh test satu proses. Tanpa
 * kunci itu dua proses membaca `Terakhir` yang sama lalu menerbitkan kode
 * kembar, dan untuk entitas yang kolom kodenya tidak berindeks unik, basis
 * data pun tidak akan menolaknya.
 *
 * Dijalankan di atas MariaDB karena SQLite tidak mengenal lockForUpdate sama
 * sekali; di sana klausanya dikompilasi menjadi kosong sehingga testnya lulus
 * tanpa menguji apa pun. Barisnya harus terlihat oleh proses anak, jadi test
 * ini tidak memakai RefreshDatabase dan membereskan barisnya sendiri.
 */
final class KodeOtomatisKonkurensiTest extends TestCase
{
    private const KONEKSI = 'mysql_paralel';

    private const ENTITAS = 'KodeParalelTest';

    private string $organisasiId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Ekstensi pcntl tidak tersedia di lingkungan ini.');
        }

        $bawaan = (string) config('database.default');

        if ((string) config("database.connections.{$bawaan}.driver") !== 'mysql') {
            $this->markTestSkipped('Konkurensi penghitung kode hanya bermakna di atas MySQL/MariaDB.');
        }

        config(['database.connections.'.self::KONEKSI => config("database.connections.{$bawaan}")]);

        $this->organisasiId = (string) Str::ulid();
    }

    protected function tearDown(): void
    {
        DB::connection(self::KONEKSI)->table('UrutanKode')
            ->where('OrganisasiId', $this->organisasiId)->delete();
        DB::purge(self::KONEKSI);

        parent::tearDown();
    }

    public function test_kode_paralel_dari_banyak_proses_tidak_menghasilkan_duplikat(): void
    {
        $jumlahProses = 5;
        $iterasiPerProses = 10;
        $totalDiharapkan = $jumlahProses * $iterasiPerProses;

        // Penghitung sengaja tidak disiapkan: layanannya harus membuatnya sendiri.
        DB::purge(self::KONEKSI);

        $direktoriHasil = sys_get_temp_dir().'/kode_otomatis_hasil_'.Str::random(8);
        mkdir($direktoriHasil);

        $pidAnak = [];
        for ($i = 0; $i < $jumlahProses; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('pcntl_fork gagal.');
            }

            if ($pid === 0) {
                $this->jalankanSebagaiAnak($iterasiPerProses, "{$direktoriHasil}/{$i}.txt");
                exit(0);
            }

            $pidAnak[] = $pid;
        }

        foreach ($pidAnak as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertSame(0, pcntl_wexitstatus($status), "Proses anak {$pid} keluar dengan status error.");
        }

        $berkasError = glob("{$direktoriHasil}/*.error");
        if ($berkasError !== [] && $berkasError !== false) {
            $this->fail('Proses anak melempar exception: '.file_get_contents($berkasError[0]));
        }

        $semuaKode = [];
        foreach (glob("{$direktoriHasil}/*.txt") ?: [] as $berkas) {
            $semuaKode = array_merge($semuaKode, array_filter(explode("\n", (string) file_get_contents($berkas))));
            unlink($berkas);
        }
        rmdir($direktoriHasil);

        $this->assertCount($totalDiharapkan, $semuaKode, 'Sebagian kode hilang -- ada proses yang gagal menulis.');
        $this->assertCount($totalDiharapkan, array_unique($semuaKode), 'Ditemukan kode duplikat lintas proses.');

        $angka = array_map(static fn (string $kode): int => (int) preg_replace('/\D/', '', $kode), $semuaKode);
        sort($angka);
        $this->assertSame(range(1, $totalDiharapkan), $angka, 'Urutan kode bolong -- ada increment yang hilang.');
    }

    private function jalankanSebagaiAnak(int $iterasi, string $pathHasil): void
    {
        try {
            DB::purge(self::KONEKSI);
            config(['database.default' => self::KONEKSI]);

            $layanan = app(LayananKodeOtomatis::class);
            $baris = [];
            for ($i = 0; $i < $iterasi; $i++) {
                $baris[] = $layanan->berikutnya(
                    entitas: self::ENTITAS,
                    awalan: 'PRL',
                    organisasiId: $this->organisasiId,
                    sudahDipakai: static fn (): bool => false,
                );
            }

            file_put_contents($pathHasil, implode("\n", $baris));
        } catch (\Throwable $e) {
            file_put_contents($pathHasil.'.error', $e->getMessage()."\n".$e->getTraceAsString());
        }
    }
}
