<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Tests\Dukungan\GambarUji;
use Tests\TestCase;

/**
 * Perintah `berkas:pampatkan` (PRD 11.1, TASK 42.03): berkas lama dipadatkan
 * per organisasi, pratinjau tidak menulis, idempoten, salinan bersama
 * dipindah bersama, dan salinan asli tidak pernah hilang sebelum penggantinya
 * terbukti utuh.
 */
final class PampatkanBerkasLamaTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $pengguna;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A']);
        $this->pengguna = $this->buatPengguna($this->organisasi);
    }

    public function test_pratinjau_menghitung_penghematan_tanpa_menulis_apa_pun(): void
    {
        $csv = $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', self::csv());
        $foto = $this->berkasLama($this->organisasi, 'foto.jpg', 'image/jpeg', GambarUji::jpeg(1200, 800));
        $sebelum = Storage::disk('local')->allFiles();

        $this->artisan('berkas:pampatkan', ['--pratinjau' => true])
            ->expectsOutputToContain('Pratinjau: 2 salinan dapat dipadatkan')
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing($sebelum, Storage::disk('local')->allFiles());
        foreach ([$csv, $foto] as $berkas) {
            $segar = $this->segarkan($berkas);
            $this->assertSame(MetodeKompresi::Tidak, $segar->MetodeKompresi);
            $this->assertSame($berkas->LokasiPenyimpanan, $segar->LokasiPenyimpanan);
            $this->assertNull($segar->LokasiThumbnail);
        }
        $this->assertSame(0, $this->jumlahAudit());
    }

    public function test_berkas_lama_dipadatkan_dan_unduhannya_tetap_identik(): void
    {
        $isiCsv = self::csv();
        $csv = $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', $isiCsv);
        $isiFoto = GambarUji::denganExif(GambarUji::jpeg(1200, 800), 1);
        $foto = $this->berkasLama($this->organisasi, 'foto.jpg', 'image/jpeg', $isiFoto);
        $xlsx = $this->berkasLama($this->organisasi, 'data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'PK'.str_repeat('x', 500));

        $this->artisan('berkas:pampatkan')
            ->expectsOutputToContain('Memadatkan 2 salinan (2 baris berkas)')
            ->assertSuccessful();

        $csvBaru = $this->segarkan($csv);
        $this->assertSame(MetodeKompresi::Gzip, $csvBaru->MetodeKompresi);
        $this->assertStringEndsWith('.csv.gz', $csvBaru->LokasiPenyimpanan);
        $this->assertSame($csv->HashSha256, $csvBaru->HashSha256, 'Hash tetap hash isi asli.');
        $this->assertSame(strlen($isiCsv), $csvBaru->UkuranByte);
        $this->assertSame(strlen($isiCsv), $csvBaru->UkuranAsliByte);
        $this->assertLessThan($csvBaru->UkuranAsliByte, $csvBaru->UkuranTersimpanByte);
        Storage::disk('local')->assertMissing($csv->LokasiPenyimpanan);
        $this->assertSame($isiCsv, $this->unduh($csvBaru)->streamedContent());

        $fotoBaru = $this->segarkan($foto);
        $this->assertSame(MetodeKompresi::GambarUlang, $fotoBaru->MetodeKompresi);
        $this->assertSame('image/webp', $fotoBaru->JenisMime);
        $this->assertNotNull($fotoBaru->LokasiThumbnail);
        Storage::disk('local')->assertExists($fotoBaru->LokasiThumbnail);
        Storage::disk('local')->assertMissing($foto->LokasiPenyimpanan);
        $this->assertStringNotContainsString('GPS', (string) Storage::disk('local')->get($fotoBaru->LokasiPenyimpanan));

        $this->assertSame($xlsx->LokasiPenyimpanan, $this->segarkan($xlsx)->LokasiPenyimpanan, 'XLSX tidak pernah dibaca.');

        $audit = DB::table('CatatanAudit')->where('Aksi', 'Berkas.DipadatkanUlang')->sole();
        $this->assertSame($this->organisasi->Id, $audit->OrganisasiId);
        $ringkasan = json_decode((string) $audit->DataSesudah, true);
        $this->assertSame(2, $ringkasan['Dipadatkan']);
        $this->assertSame(strlen($isiCsv) + strlen($isiFoto), $ringkasan['UkuranSebelum']);
        $this->assertSame($csvBaru->UkuranTersimpanByte + $fotoBaru->UkuranTersimpanByte, $ringkasan['UkuranSesudah']);
    }

    public function test_dijalankan_ulang_tidak_mengubah_apa_pun(): void
    {
        $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', self::csv());
        $this->berkasLama($this->organisasi, 'derau.jpg', 'image/jpeg', GambarUji::jpegDerau(900, 700));

        $this->artisan('berkas:pampatkan')->assertSuccessful();
        $berkasSesudahPertama = Storage::disk('local')->allFiles();
        $barisSesudahPertama = $this->semuaBaris();

        $this->artisan('berkas:pampatkan')
            ->expectsOutputToContain('Memadatkan 0 salinan')
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing($berkasSesudahPertama, Storage::disk('local')->allFiles());
        $this->assertEquals($barisSesudahPertama, $this->semuaBaris());
        $this->assertSame(1, $this->jumlahAudit());
    }

    public function test_jpeg_yang_tetap_jpeg_dibuang_metadatanya_dan_dibuatkan_thumbnail(): void
    {
        $isi = GambarUji::jpegDerau(900, 700);
        $foto = $this->berkasLama($this->organisasi, 'derau.jpg', 'image/jpeg', $isi);

        $this->artisan('berkas:pampatkan')->assertSuccessful();

        $segar = $this->segarkan($foto);
        $tersimpan = (string) Storage::disk('local')->get($segar->LokasiPenyimpanan);
        $this->assertSame(MetodeKompresi::Tidak, $segar->MetodeKompresi);
        $this->assertSame('image/jpeg', $segar->JenisMime);
        $this->assertLessThan(strlen($isi), strlen($tersimpan));
        $this->assertSame(strstr($isi, "\xFF\xDA"), strstr($tersimpan, "\xFF\xDA"), 'Data piksel utuh.');
        $this->assertSame(strlen($tersimpan), $segar->UkuranTersimpanByte);
        $this->assertSame(strlen($isi), $segar->UkuranAsliByte);
        $this->assertNotNull($segar->LokasiThumbnail);
        Storage::disk('local')->assertExists($segar->LokasiThumbnail);
        Storage::disk('local')->assertMissing($foto->LokasiPenyimpanan);
    }

    public function test_gambar_yang_tidak_dapat_diperkecil_hanya_dibuatkan_thumbnail(): void
    {
        $isi = self::tanpaKomentar(GambarUji::jpegDerau(900, 700));
        $foto = $this->berkasLama($this->organisasi, 'derau.jpg', 'image/jpeg', $isi);

        $this->artisan('berkas:pampatkan')->assertSuccessful();

        $segar = $this->segarkan($foto);
        $this->assertSame(MetodeKompresi::Tidak, $segar->MetodeKompresi);
        $this->assertSame($foto->LokasiPenyimpanan, $segar->LokasiPenyimpanan);
        $this->assertSame($isi, Storage::disk('local')->get($segar->LokasiPenyimpanan));
        $this->assertNotNull($segar->LokasiThumbnail);
        Storage::disk('local')->assertExists($segar->LokasiThumbnail);
    }

    public function test_hanya_organisasi_yang_dipilih_yang_dipadatkan(): void
    {
        $organisasiB = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $milikA = $this->berkasLama($this->organisasi, 'a.csv', 'text/csv', self::csv());
        $milikB = $this->berkasLama($organisasiB, 'b.csv', 'text/csv', self::csv('B'));

        $this->artisan('berkas:pampatkan', ['--organisasi' => 'ORG-B'])->assertSuccessful();

        $this->assertSame(MetodeKompresi::Tidak, $this->segarkan($milikA)->MetodeKompresi);
        $this->assertSame(MetodeKompresi::Gzip, $this->segarkan($milikB)->MetodeKompresi);
        $this->assertSame(0, DB::table('CatatanAudit')->where('OrganisasiId', $this->organisasi->Id)->where('Aksi', 'Berkas.DipadatkanUlang')->count());
        $this->assertSame(1, DB::table('CatatanAudit')->where('OrganisasiId', $organisasiB->Id)->where('Aksi', 'Berkas.DipadatkanUlang')->count());
    }

    public function test_batas_membatasi_jumlah_salinan_yang_diperiksa(): void
    {
        $this->berkasLama($this->organisasi, 'a.csv', 'text/csv', self::csv('A'));
        $this->berkasLama($this->organisasi, 'b.csv', 'text/csv', self::csv('B'));
        $this->berkasLama($this->organisasi, 'c.csv', 'text/csv', self::csv('C'));

        $this->artisan('berkas:pampatkan', ['--batas' => '2'])->assertSuccessful();

        $this->assertSame(2, collect($this->semuaBaris())->where('MetodeKompresi', MetodeKompresi::Gzip->value)->count());

        $this->artisan('berkas:pampatkan', ['--batas' => 'dua'])->assertFailed();
    }

    public function test_semua_baris_yang_berbagi_satu_salinan_dipindah_bersama(): void
    {
        $isi = self::csv();
        $pertama = $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', $isi);
        $kedua = $this->barisBerbagi($pertama, 'aset-salinan.csv');
        $terhapus = $this->barisBerbagi($pertama, 'aset-lama.csv');
        $terhapus->delete();

        $this->artisan('berkas:pampatkan')
            ->expectsOutputToContain('Memadatkan 1 salinan (2 baris berkas)')
            ->assertSuccessful();

        $baru = $this->segarkan($pertama);
        $this->assertSame(MetodeKompresi::Gzip, $baru->MetodeKompresi);
        $this->assertSame($baru->LokasiPenyimpanan, $this->segarkan($kedua)->LokasiPenyimpanan);
        $this->assertSame(MetodeKompresi::Gzip, $this->segarkan($kedua)->MetodeKompresi);
        $this->assertSame($baru->LokasiPenyimpanan, $this->segarkan($terhapus)->LokasiPenyimpanan);
        Storage::disk('local')->assertMissing($pertama->LokasiPenyimpanan);
        $this->assertSame($isi, $this->unduh($this->segarkan($kedua))->streamedContent());
    }

    public function test_salinan_asli_tetap_bila_salinan_baru_gagal_diverifikasi(): void
    {
        $this->pakaiDiskYangMerusakGzip();
        $isi = self::csv();
        $berkas = $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', $isi, 'rusak');

        $this->artisan('berkas:pampatkan')
            ->expectsOutputToContain('gagal dipadatkan')
            ->assertSuccessful();

        $segar = $this->segarkan($berkas);
        $this->assertSame(MetodeKompresi::Tidak, $segar->MetodeKompresi);
        $this->assertSame($berkas->LokasiPenyimpanan, $segar->LokasiPenyimpanan);
        $this->assertSame($isi, Storage::disk('rusak')->get($berkas->LokasiPenyimpanan));
        $this->assertSame([$berkas->LokasiPenyimpanan], Storage::disk('rusak')->allFiles(), 'Salinan gagal tidak ditinggalkan.');
    }

    public function test_isi_yang_tidak_cocok_dengan_hash_tercatat_dilewati(): void
    {
        $berkas = $this->berkasLama($this->organisasi, 'aset.csv', 'text/csv', self::csv());
        Storage::disk('local')->put($berkas->LokasiPenyimpanan, self::csv('berubah'));

        $this->artisan('berkas:pampatkan')->assertSuccessful();

        $segar = $this->segarkan($berkas);
        $this->assertSame(MetodeKompresi::Tidak, $segar->MetodeKompresi);
        Storage::disk('local')->assertExists($berkas->LokasiPenyimpanan);
    }

    /** JPEG tanpa segmen komentar (COM) yang ditulis GD: tidak ada metadata yang dapat dibuang. */
    private static function tanpaKomentar(string $jpeg): string
    {
        $awal = strpos($jpeg, "\xFF\xFE");
        if ($awal === false) {
            return $jpeg;
        }

        $panjang = unpack('n', substr($jpeg, $awal + 2, 2));

        return substr($jpeg, 0, $awal).substr($jpeg, $awal + 2 + (int) ($panjang[1] ?? 0));
    }

    private static function csv(string $awalan = 'AST'): string
    {
        return "Kode,Nama,Lokasi\n".implode("\n", array_map(fn (int $i): string => "{$awalan}-{$i},Pompa infus {$i},Ruang ICU", range(1, 800)));
    }

    /** Baris seperti yang dibuat sebelum mesin kompresi ada: tanpa kompresi, ukuran sudah di-backfill. */
    private function berkasLama(Organisasi $organisasi, string $nama, string $mime, string $isi, string $disk = 'local'): Berkas
    {
        $lokasi = 'berkas/'.$organisasi->Id.'/'.uniqid().'.'.pathinfo($nama, PATHINFO_EXTENSION);
        Storage::disk($disk)->put($lokasi, $isi);

        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);
        $berkas = Berkas::create([
            'NamaAsli' => $nama,
            'NamaPenyimpanan' => basename($lokasi),
            'MediaPenyimpanan' => $disk,
            'LokasiPenyimpanan' => $lokasi,
            'JenisMime' => $mime,
            'UkuranByte' => strlen($isi),
            'UkuranAsliByte' => strlen($isi),
            'UkuranTersimpanByte' => strlen($isi),
            'HashSha256' => hash('sha256', $isi),
            'DiunggahOleh' => $organisasi->is($this->organisasi) ? $this->pengguna->Id : null,
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        return $berkas;
    }

    private function barisBerbagi(Berkas $asal, string $nama): Berkas
    {
        app(KonteksOrganisasi::class)->tetapkan((string) $asal->OrganisasiId);
        $berkas = Berkas::create([
            ...$asal->only(['NamaPenyimpanan', 'MediaPenyimpanan', 'LokasiPenyimpanan', 'JenisMime', 'UkuranByte', 'UkuranAsliByte', 'UkuranTersimpanByte', 'HashSha256', 'DiunggahOleh']),
            'NamaAsli' => $nama,
        ]);
        app(KonteksOrganisasi::class)->bersihkan();

        return $berkas;
    }

    /**
     * Disk yang merusak setiap salinan `.gz` yang ditulis ke sana: membuktikan
     * verifikasi baca-balik, bukan sekadar "tulis berhasil".
     */
    private function pakaiDiskYangMerusakGzip(): void
    {
        $akar = storage_path('framework/testing/disks/rusak-'.uniqid());
        config(['filesystems.disks.rusak' => ['driver' => 'rusak', 'root' => $akar]]);

        Storage::extend('rusak', function ($app, array $config): FilesystemAdapter {
            $adapter = new class((string) $config['root']) extends LocalFilesystemAdapter
            {
                public function writeStream(string $path, $contents, Config $config): void
                {
                    if (str_ends_with($path, '.gz')) {
                        $contents = fopen('data://text/plain,rusak', 'rb');
                    }

                    parent::writeStream($path, $contents, $config);
                }
            };

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }

    private function segarkan(Berkas $berkas): Berkas
    {
        return Berkas::withoutGlobalScopes()->findOrFail($berkas->Id);
    }

    /** @return list<array<string, mixed>> */
    private function semuaBaris(): array
    {
        return DB::table('Berkas')->orderBy('Id')->get()->map(fn (object $baris): array => (array) $baris)->all();
    }

    private function jumlahAudit(): int
    {
        return DB::table('CatatanAudit')->where('Aksi', 'Berkas.DipadatkanUlang')->count();
    }

    /** @return TestResponse<Response> */
    private function unduh(Berkas $berkas): TestResponse
    {
        $respons = $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.unduh', $berkas));
        $respons->assertOk();

        return $respons;
    }

    private function buatPengguna(Organisasi $organisasi): Pengguna
    {
        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }
}
