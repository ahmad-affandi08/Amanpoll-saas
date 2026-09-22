<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Core\Cadangan\BerkasCadangan;
use App\Core\Cadangan\LayananCadangan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Gate 25: pemulihan benar-benar dijalankan, bukan hanya pekerjaan cadangan yang tersedia.
 *
 * Test ini mencadangkan basis data uji, memulihkannya ke basis data lain, lalu
 * membandingkan isinya. Pemulihan sengaja tidak diarahkan ke basis data uji itu
 * sendiri: memulihkan ke dirinya sendiri akan meruntuhkan transaksi pembungkus
 * test dan mengotori test berikutnya, dan yang hendak dibuktikan bukan itu
 * melainkan bahwa berkas dumpnya sungguh dapat dipulihkan.
 */
final class PemulihanCadanganTest extends TestCase
{
    private string $basisDataUji;

    private string $folderCadangan;

    /**
     * Kode organisasi yang ditulis test ini.
     *
     * Test ini tidak memakai RefreshDatabase — pemulihan adalah operasi DDL yang
     * akan meruntuhkan transaksi pembungkusnya — jadi barisnya nyata dan harus
     * dibereskan sendiri, bukan ditinggalkan mengotori test berikutnya.
     *
     * @var list<string>
     */
    private array $kodeDitulis = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->lewatiBilaBinerTidakAda();

        $this->folderCadangan = storage_path('app/private/cadangan-uji-'.Str::lower(Str::random(6)));
        config(['amanpoll.cadangan.folder' => basename($this->folderCadangan)]);

        $this->basisDataUji = 'amanpoll_pulih_'.Str::lower(Str::random(6));
        DB::statement("CREATE DATABASE `{$this->basisDataUji}` CHARACTER SET utf8mb4");
    }

    protected function tearDown(): void
    {
        if ($this->kodeDitulis !== []) {
            DB::table('Organisasi')->whereIn('Kode', $this->kodeDitulis)->delete();
        }

        DB::statement("DROP DATABASE IF EXISTS `{$this->basisDataUji}`");
        File::deleteDirectory($this->folderCadangan);
        DB::purge('pemulihan_uji');

        parent::tearDown();
    }

    /** Inti Gate 25: isi yang dipulihkan harus sama dengan isi saat dicadangkan. */
    public function test_cadangan_basis_data_benar_benar_dapat_dipulihkan(): void
    {
        $penanda = 'ORG-CADANGAN-'.Str::upper(Str::random(8));
        $this->tulisOrganisasi($penanda);

        $dump = app(LayananCadangan::class)->cadangkanBasisData();

        $this->assertFileExists($dump);
        $this->assertGreaterThan(0, File::size($dump));

        app(LayananCadangan::class)->pulihkanBasisData($dump, $this->basisDataUji);

        $this->assertSame(
            1,
            $this->hitungDiBasisDataUji('SELECT count(*) AS jumlah FROM Organisasi WHERE Kode = ?', [$penanda]),
            'Baris yang ada saat pencadangan tidak ditemukan setelah pemulihan.',
        );
    }

    /** Perubahan setelah pencadangan tidak boleh ikut terbawa; kalau ikut, yang dipulihkan bukan dump itu. */
    public function test_pemulihan_mengembalikan_keadaan_saat_dicadangkan(): void
    {
        $sebelum = 'ORG-SEBELUM-'.Str::upper(Str::random(8));
        $this->tulisOrganisasi($sebelum);

        $dump = app(LayananCadangan::class)->cadangkanBasisData();

        $sesudah = 'ORG-SESUDAH-'.Str::upper(Str::random(8));
        $this->tulisOrganisasi($sesudah);

        app(LayananCadangan::class)->pulihkanBasisData($dump, $this->basisDataUji);

        $this->assertSame(
            1,
            $this->hitungDiBasisDataUji('SELECT count(*) AS jumlah FROM Organisasi WHERE Kode = ?', [$sebelum]),
        );
        $this->assertSame(
            0,
            $this->hitungDiBasisDataUji('SELECT count(*) AS jumlah FROM Organisasi WHERE Kode = ?', [$sesudah]),
            'Baris yang lahir setelah pencadangan ikut terbawa; dump yang dipulihkan bukan yang dimaksud.',
        );
    }

    /**
     * Dump yang "berhasil" tetapi tidak mengeluarkan apa pun harus ditolak.
     *
     * `true` adalah perintah yang selalu sukses tanpa keluaran, jadi ia meniru
     * mysqldump yang gagal diam-diam. Memeriksa ukuran saja tidak menangkapnya:
     * gzip atas masukan kosong tetap menghasilkan berkas belasan byte.
     */
    public function test_dump_yang_tidak_memuat_tabel_ditolak(): void
    {
        config(['amanpoll.cadangan.mysqldump' => 'true']);

        try {
            app(LayananCadangan::class)->cadangkanBasisData();
            $this->fail('Dump tanpa definisi tabel seharusnya ditolak.');
        } catch (AturanBisnisDilanggar $galat) {
            $this->assertStringContainsString('definisi tabel', $galat->getMessage());
        }

        $this->assertSame([], app(BerkasCadangan::class)->semua(), 'Dump tidak sehat harus ikut dihapus.');
    }

    /** Biner yang tidak ada harus ketahuan sekarang, bukan pada hari pemulihan dibutuhkan. */
    public function test_biner_mysqldump_yang_tidak_ada_ditolak(): void
    {
        config(['amanpoll.cadangan.mysqldump' => '/jalur/yang/tidak/ada/mysqldump']);

        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('tidak ditemukan');

        app(LayananCadangan::class)->cadangkanBasisData();
    }

    public function test_biner_mysql_yang_tidak_ada_ditolak_saat_memulihkan(): void
    {
        $dump = app(LayananCadangan::class)->cadangkanBasisData();
        config(['amanpoll.cadangan.mysql' => '/jalur/yang/tidak/ada/mysql']);

        $this->expectException(AturanBisnisDilanggar::class);
        $this->expectExceptionMessage('tidak ditemukan');

        app(LayananCadangan::class)->pulihkanBasisData($dump, $this->basisDataUji);
    }

    public function test_memulihkan_berkas_yang_tidak_ada_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananCadangan::class)->pulihkanBasisData(
            $this->folderCadangan.'/basisdata-20000101-000000.sql.gz',
            $this->basisDataUji,
        );
    }

    public function test_memulihkan_berkas_kosong_ditolak(): void
    {
        File::ensureDirectoryExists($this->folderCadangan);
        $kosong = $this->folderCadangan.'/basisdata-20260101-000000.sql.gz';
        File::put($kosong, '');

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananCadangan::class)->pulihkanBasisData($kosong, $this->basisDataUji);
    }

    /** Retensi menghapus yang lewat umurnya dan menyisakan yang masih dalam jendela. */
    public function test_pemangkasan_menghapus_cadangan_yang_lewat_retensi(): void
    {
        config(['amanpoll.cadangan.retensi_hari' => 7]);

        $berkas = app(BerkasCadangan::class);
        File::ensureDirectoryExists($this->folderCadangan);

        $lama = $berkas->jalurBaru('basisdata', 'sql.gz', CarbonImmutable::now()->subDays(30));
        $baru = $berkas->jalurBaru('basisdata', 'sql.gz', CarbonImmutable::now()->subDay());
        File::put($lama, 'x');
        File::put($baru, 'x');

        $dihapus = app(LayananCadangan::class)->pangkas();

        $this->assertSame(1, $dihapus);
        $this->assertFileDoesNotExist($lama);
        $this->assertFileExists($baru);
    }

    /** Berkas yang tidak berpola nama cadangan bukan milik kita dan tidak boleh ikut terhapus. */
    public function test_pemangkasan_tidak_menyentuh_berkas_asing(): void
    {
        config(['amanpoll.cadangan.retensi_hari' => 1]);

        File::ensureDirectoryExists($this->folderCadangan);
        $asing = $this->folderCadangan.'/catatan-penting.txt';
        File::put($asing, 'jangan dihapus');

        app(LayananCadangan::class)->pangkas();

        $this->assertFileExists($asing);
    }

    private function tulisOrganisasi(string $kode): void
    {
        $this->kodeDitulis[] = $kode;

        DB::table('Organisasi')->insert([
            'Id' => (string) Str::ulid(),
            'Kode' => $kode,
            'Nama' => 'Organisasi '.$kode,
            'Status' => 'Aktif',
            'DibuatPada' => now(),
            'DiperbaruiPada' => now(),
        ]);
    }

    /** @param list<mixed> $ikatan */
    private function hitungDiBasisDataUji(string $sql, array $ikatan): int
    {
        $koneksi = (string) config('database.default');
        config(['database.connections.pemulihan_uji' => array_merge(
            (array) config("database.connections.{$koneksi}"),
            ['database' => $this->basisDataUji],
        )]);

        DB::purge('pemulihan_uji');

        return (int) DB::connection('pemulihan_uji')->selectOne($sql, $ikatan)->jumlah;
    }

    private function lewatiBilaBinerTidakAda(): void
    {
        foreach (['mysqldump', 'mysql'] as $biner) {
            exec('command -v '.escapeshellarg($biner), $keluaran, $kode);

            if ($kode !== 0) {
                $this->markTestSkipped("Biner {$biner} tidak tersedia, pemulihan tidak dapat diuji di sini.");
            }
        }
    }
}
