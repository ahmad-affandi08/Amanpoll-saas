<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Membuktikan LayananNomorDokumen::berikutnya() aman dari race condition
 * memakai proses OS sungguhan (pcntl_fork), bukan simulasi single-process --
 * request paralel yang sesungguhnya, sesuai TASK.md 04.05.
 *
 * SQLite tidak mendukung row-level lock ("FOR UPDATE" adalah no-op di
 * grammar-nya), tapi tetap men-serialize transaksi tulis di level file;
 * PRAGMA busy_timeout dipasang supaya penulis kedua MENUNGGU (seperti
 * InnoDB row lock di produksi/MySQL), bukan langsung gagal dengan
 * "database is locked". Baris kode yang diuji (DB::transaction +
 * lockForUpdate) sama persis dengan yang berjalan di produksi.
 */
class NomorDokumenKonkurensiTest extends TestCase
{
    private string $pathDb;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Ekstensi pcntl tidak tersedia di lingkungan ini.');
        }

        $this->pathDb = sys_get_temp_dir().'/nomor_dokumen_paralel_'.Str::random(8).'.sqlite';
        touch($this->pathDb);

        config(['database.connections.sqlite_paralel' => [
            'driver' => 'sqlite',
            'database' => $this->pathDb,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        DB::connection('sqlite_paralel')->statement('PRAGMA busy_timeout = 5000');
        Artisan::call('migrate', ['--database' => 'sqlite_paralel', '--force' => true]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite_paralel');
        @unlink($this->pathDb);

        parent::tearDown();
    }

    public function test_generate_nomor_paralel_dari_banyak_proses_tidak_menghasilkan_duplikat(): void
    {
        $jumlahProses = 5;
        $iterasiPerProses = 10;
        $totalDiharapkan = $jumlahProses * $iterasiPerProses;

        $organisasiId = (string) Str::ulid();
        DB::connection('sqlite_paralel')->table('Organisasi')->insert([
            'Id' => $organisasiId, 'Kode' => 'ORG-PARALEL', 'Nama' => 'Organisasi Paralel',
            'DibuatPada' => now(), 'DiperbaruiPada' => now(),
        ]);
        DB::connection('sqlite_paralel')->table('NomorDokumen')->insert([
            'Id' => (string) Str::ulid(), 'OrganisasiId' => $organisasiId, 'JenisDokumen' => 'ParalelTest',
            'Awalan' => 'PK', 'FormatNomor' => '{Awalan}-{Nomor:5}', 'NomorTerakhir' => 0,
            'ResetPeriode' => 'TidakAda', 'DibuatPada' => now(), 'DiperbaruiPada' => now(),
        ]);
        DB::purge('sqlite_paralel');

        $direktoriHasil = sys_get_temp_dir().'/nomor_dokumen_hasil_'.Str::random(8);
        mkdir($direktoriHasil);

        $pidAnak = [];
        for ($i = 0; $i < $jumlahProses; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('pcntl_fork gagal.');
            }

            if ($pid === 0) {
                $this->jalankanSebagaiAnak($organisasiId, $iterasiPerProses, "{$direktoriHasil}/{$i}.txt");
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

        $semuaNomor = [];
        foreach (glob("{$direktoriHasil}/*.txt") as $berkas) {
            $semuaNomor = array_merge($semuaNomor, array_filter(explode("\n", (string) file_get_contents($berkas))));
            unlink($berkas);
        }
        rmdir($direktoriHasil);

        $this->assertCount($totalDiharapkan, $semuaNomor, 'Sebagian nomor hilang -- ada proses yang gagal menulis.');
        $this->assertCount($totalDiharapkan, array_unique($semuaNomor), 'Ditemukan nomor dokumen duplikat lintas proses.');

        $angka = array_map(fn (string $nomor): int => (int) preg_replace('/\D/', '', $nomor), $semuaNomor);
        sort($angka);
        $this->assertSame(range(1, $totalDiharapkan), $angka, 'Urutan nomor bolong -- ada increment yang tidak konsisten.');
    }

    private function jalankanSebagaiAnak(string $organisasiId, int $iterasi, string $pathHasil): void
    {
        try {
            DB::purge('sqlite_paralel');
            config(['database.default' => 'sqlite_paralel']);
            DB::connection('sqlite_paralel')->statement('PRAGMA busy_timeout = 5000');

            $layanan = app(LayananNomorDokumen::class);
            $baris = [];
            for ($i = 0; $i < $iterasi; $i++) {
                $baris[] = $layanan->berikutnya($organisasiId, 'ParalelTest');
            }

            file_put_contents($pathHasil, implode("\n", $baris));
        } catch (\Throwable $e) {
            file_put_contents($pathHasil.'.error', $e->getMessage()."\n".$e->getTraceAsString());
        }
    }
}
