<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Platform\Application\Services\LayananNomorDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Membuktikan LayananNomorDokumen::berikutnya() aman dari race condition memakai proses OS sungguhan.
 *
 * Dijalankan di atas MariaDB, bukan SQLite. Penjaga yang diuji adalah
 * `lockForUpdate()`, dan SQLite tidak mengenalnya sama sekali — grammar-nya
 * mengompilasi klausa itu menjadi kosong. Di sana transaksi hanya memegang
 * kunci baca lalu berebut naik menjadi kunci tulis, sehingga hasilnya bukan
 * "aman dari race" melainkan `database is locked` ketika mesin sedang sibuk.
 * Test yang lulus karena mesinnya tidak mendukung mekanisme yang diuji tidak
 * membuktikan apa pun.
 *
 * Karena barisnya harus terlihat oleh proses anak, test ini tidak memakai
 * RefreshDatabase dan membereskan barisnya sendiri.
 */
class NomorDokumenKonkurensiTest extends TestCase
{
    private const KONEKSI = 'mysql_paralel';

    private string $organisasiId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Ekstensi pcntl tidak tersedia di lingkungan ini.');
        }

        $bawaan = (string) config('database.default');

        if ((string) config("database.connections.{$bawaan}.driver") !== 'mysql') {
            $this->markTestSkipped('Konkurensi penomoran hanya bermakna di atas MySQL/MariaDB.');
        }

        // Koneksi terpisah dengan setelan yang sama; anak memakainya tanpa mewarisi transaksi induk.
        config(['database.connections.'.self::KONEKSI => config("database.connections.{$bawaan}")]);

        $this->organisasiId = (string) Str::ulid();
    }

    protected function tearDown(): void
    {
        DB::connection(self::KONEKSI)->table('NomorDokumen')
            ->where('OrganisasiId', $this->organisasiId)->delete();
        DB::connection(self::KONEKSI)->table('Organisasi')
            ->where('Id', $this->organisasiId)->delete();
        DB::purge(self::KONEKSI);

        parent::tearDown();
    }

    public function test_generate_nomor_paralel_dari_banyak_proses_tidak_menghasilkan_duplikat(): void
    {
        $jumlahProses = 5;
        $iterasiPerProses = 10;
        $totalDiharapkan = $jumlahProses * $iterasiPerProses;

        $organisasiId = $this->organisasiId;
        DB::connection(self::KONEKSI)->table('Organisasi')->insert([
            'Id' => $organisasiId, 'Kode' => 'ORG-PARALEL-'.Str::upper(Str::random(6)),
            'Nama' => 'Organisasi Paralel', 'Status' => 'Aktif',
            'DibuatPada' => now(), 'DiperbaruiPada' => now(),
        ]);
        DB::connection(self::KONEKSI)->table('NomorDokumen')->insert([
            'Id' => (string) Str::ulid(), 'OrganisasiId' => $organisasiId, 'JenisDokumen' => 'ParalelTest',
            'Awalan' => 'PK', 'FormatNomor' => '{Awalan}-{Nomor:5}', 'NomorTerakhir' => 0,
            'ResetPeriode' => 'TidakAda', 'DibuatPada' => now(), 'DiperbaruiPada' => now(),
        ]);

        // Anak adalah proses terpisah; barisnya harus sudah commit sebelum fork.
        DB::purge(self::KONEKSI);

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
            DB::purge(self::KONEKSI);
            config(['database.default' => self::KONEKSI]);

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
