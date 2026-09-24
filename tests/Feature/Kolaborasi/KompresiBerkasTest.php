<?php

declare(strict_types=1);

namespace Tests\Feature\Kolaborasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Infrastructure\Ekspor\PenulisEksporXlsx;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Dukungan\GambarUji;
use Tests\TestCase;

/**
 * Mesin kompresi pada jalur unggah dan unduh Kolaborasi (PRD 11.1): pemampatan
 * per jenis, unduhan yang selalu mengembalikan isi asli, thumbnail terotorisasi,
 * dan berbagi salinan fisik dalam satu organisasi.
 */
class KompresiBerkasTest extends TestCase
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

    public function test_jpeg_besar_ber_exif_disimpan_sebagai_webp_lurus_tanpa_gps_dengan_thumbnail(): void
    {
        $asli = GambarUji::denganExif(GambarUji::jpeg(3000, 2000), 6);
        $this->assertStringContainsString('GPS', (string) json_encode(exif_read_data('data://image/jpeg;base64,'.base64_encode($asli))));

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('Kamera 01.jpg', $asli));

        $this->assertSame(MetodeKompresi::GambarUlang, $berkas->MetodeKompresi);
        $this->assertSame('image/webp', $berkas->JenisMime);
        $this->assertStringEndsWith('.webp', $berkas->LokasiPenyimpanan);
        $this->assertSame(hash('sha256', $asli), $berkas->HashSha256, 'Hash dihitung atas isi asli.');
        $this->assertSame(strlen($asli), $berkas->UkuranAsliByte);
        $this->assertLessThan($berkas->UkuranAsliByte, $berkas->UkuranTersimpanByte);
        $this->assertSame($berkas->UkuranTersimpanByte, $berkas->UkuranByte);

        $tersimpan = (string) Storage::disk('local')->get($berkas->LokasiPenyimpanan);
        $this->assertStringNotContainsString('Exif', $tersimpan);
        $this->assertStringNotContainsString('GPS', $tersimpan);
        $gambar = GambarUji::gambarDari($tersimpan);
        // Orientation 6: potret, sisi terpanjang dibatasi 2560, kotak merah pindah ke kanan atas.
        $this->assertSame([1707, 2560], [imagesx($gambar), imagesy($gambar)]);
        $this->assertGreaterThan(200, GambarUji::piksel($gambar, 1700, 10)[0]);
        $this->assertLessThan(100, GambarUji::piksel($gambar, 10, 10)[0]);

        $this->assertNotNull($berkas->LokasiThumbnail);
        $thumbnail = GambarUji::gambarDari((string) Storage::disk('local')->get($berkas->LokasiThumbnail));
        $this->assertSame(480, max(imagesx($thumbnail), imagesy($thumbnail)));

        $unduhan = $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.unduh', $berkas));
        $unduhan->assertOk();
        $unduhan->assertHeader('Content-Type', 'image/webp');
        $this->assertStringContainsString('Kamera 01.webp', (string) $unduhan->headers->get('Content-Disposition'));
        $this->assertSame($tersimpan, $unduhan->streamedContent());
    }

    public function test_png_transparan_tetap_transparan_setelah_dikodekan_ulang(): void
    {
        $asli = GambarUji::pngTransparan();

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('logo.png', $asli));

        $this->assertSame(MetodeKompresi::GambarUlang, $berkas->MetodeKompresi);
        $gambar = GambarUji::gambarDari((string) Storage::disk('local')->get($berkas->LokasiPenyimpanan));
        $this->assertSame(127, GambarUji::piksel($gambar, 50, 50)[3], 'Separuh kiri tetap transparan.');
        $this->assertSame(0, GambarUji::piksel($gambar, 700, 300)[3], 'Separuh kanan tetap pekat.');
    }

    public function test_gambar_yang_webp_nya_lebih_besar_disimpan_asli(): void
    {
        $asli = GambarUji::jpegDerau();

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('derau.jpg', $asli));

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertSame('image/jpeg', $berkas->JenisMime);
        $this->assertStringEndsWith('.jpg', $berkas->LokasiPenyimpanan);
        // Komentar JPEG dari GD termasuk metadata yang dibuang; data pikselnya utuh.
        $tersimpan = (string) Storage::disk('local')->get($berkas->LokasiPenyimpanan);
        $this->assertSame(strstr($asli, "\xFF\xDA"), strstr($tersimpan, "\xFF\xDA"));
        $unduhan = $this->unduh($berkas);
        $unduhan->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('derau.jpg', (string) $unduhan->headers->get('Content-Disposition'));
    }

    public function test_csv_di_gzip_dan_unduhannya_identik_dengan_asli(): void
    {
        $csv = "Kode,Nama,Lokasi\n".implode("\n", array_map(fn (int $i): string => "AST-{$i},Pompa infus {$i},Ruang ICU", range(1, 2000)));

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('aset.csv', $csv));

        $this->assertSame(MetodeKompresi::Gzip, $berkas->MetodeKompresi);
        $this->assertStringEndsWith('.csv.gz', $berkas->LokasiPenyimpanan);
        $this->assertSame(strlen($csv), $berkas->UkuranByte);
        $this->assertSame(strlen($csv), $berkas->UkuranAsliByte);
        $this->assertLessThan(strlen($csv) / 4, $berkas->UkuranTersimpanByte);
        $this->assertSame($csv, gzdecode((string) Storage::disk('local')->get($berkas->LokasiPenyimpanan)));

        $unduhan = $this->unduh($berkas);
        $this->assertSame($csv, $unduhan->streamedContent());
        $this->assertStringStartsWith('text/', (string) $unduhan->headers->get('Content-Type'));
        $this->assertSame((string) strlen($csv), $unduhan->headers->get('Content-Length'));
        $this->assertStringContainsString('aset.csv', (string) $unduhan->headers->get('Content-Disposition'));
    }

    public function test_pdf_yang_sudah_padat_tidak_di_gzip(): void
    {
        mt_srand(3);
        $pdf = "%PDF-1.4\n".implode('', array_map(fn (): string => chr(mt_rand(0, 255)), range(1, 30000)))."\n%%EOF";

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('scan.pdf', $pdf));

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertSame($pdf, Storage::disk('local')->get($berkas->LokasiPenyimpanan));
        $this->assertSame($pdf, $this->unduh($berkas)->streamedContent());
    }

    public function test_pdf_yang_hemat_di_gzip_dan_unduhannya_identik(): void
    {
        $pdf = "%PDF-1.4\n".str_repeat("1 0 obj << /Type /Page /Contents (Laporan pemeliharaan) >> endobj\n", 400).'%%EOF';

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('laporan.pdf', $pdf));

        $this->assertSame(MetodeKompresi::Gzip, $berkas->MetodeKompresi);
        $this->assertSame('application/pdf', $berkas->JenisMime);
        $unduhan = $this->unduh($berkas);
        $this->assertSame($pdf, $unduhan->streamedContent());
        $unduhan->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_yang_hematnya_di_bawah_ambang_tidak_di_gzip(): void
    {
        // Separuh acak, separuh berulang: gzip menghemat sekitar separuhnya.
        mt_srand(5);
        $acak = implode('', array_map(fn (): string => chr(mt_rand(0, 255)), range(1, 10000)));
        $pdf = "%PDF-1.4\n".$acak.str_repeat("1 0 obj << /Type /Page >> endobj\n", 300).'%%EOF';

        config(['amanpoll.kompresi.gzip.hemat_minimal_persen' => 60]);
        $ditahan = $this->unggah(UploadedFile::fake()->createWithContent('campuran.pdf', $pdf));
        $this->assertSame(MetodeKompresi::Tidak, $ditahan->MetodeKompresi);

        config(['amanpoll.kompresi.gzip.hemat_minimal_persen' => 10]);
        $dipadatkan = $this->unggah(UploadedFile::fake()->createWithContent('campuran.pdf', $pdf));
        $this->assertSame(MetodeKompresi::Gzip, $dipadatkan->MetodeKompresi);
    }

    public function test_xlsx_disimpan_apa_adanya(): void
    {
        $lokasi = (string) tempnam(sys_get_temp_dir(), 'uji-xlsx-');
        (new PenulisEksporXlsx)->tulis($lokasi, ['Kode', 'Nama'], array_fill(0, 200, ['AST-1', 'Pompa infus']), []);
        $xlsx = (string) file_get_contents($lokasi);
        unlink($lokasi);

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('aset.xlsx', $xlsx));

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertStringEndsWith('.xlsx', $berkas->LokasiPenyimpanan);
        $this->assertSame($xlsx, Storage::disk('local')->get($berkas->LokasiPenyimpanan));
    }

    public function test_gambar_rusak_tetap_terunggah_apa_adanya_dan_dicatat_di_log(): void
    {
        Log::spy();
        $utuh = GambarUji::jpeg(600, 400);
        $rusak = substr($utuh, 0, (int) (strlen($utuh) * 0.6));

        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('rusak.jpg', $rusak));

        $this->assertSame(MetodeKompresi::Tidak, $berkas->MetodeKompresi);
        $this->assertSame($rusak, Storage::disk('local')->get($berkas->LokasiPenyimpanan));
        $this->assertNull($berkas->LokasiThumbnail);
        Log::shouldHaveReceived('warning')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'Kompresi berkas gagal'))->once();
    }

    public function test_unggahan_identik_berbagi_satu_salinan_fisik_dan_dihapus_aman(): void
    {
        $png = GambarUji::pngTransparan();

        $pertama = $this->unggah(UploadedFile::fake()->createWithContent('a.png', $png));
        $kedua = $this->unggah(UploadedFile::fake()->createWithContent('b.png', $png));

        $this->assertNotSame($pertama->Id, $kedua->Id);
        $this->assertSame($pertama->LokasiPenyimpanan, $kedua->LokasiPenyimpanan);
        $this->assertNotNull($pertama->LokasiThumbnail);
        $this->assertSame($pertama->LokasiThumbnail, $kedua->LokasiThumbnail);
        $this->assertSame('b.png', $kedua->NamaAsli);
        $this->assertCount(1, Storage::disk('local')->files('berkas/'.$this->organisasi->Id));
        $this->assertCount(1, Storage::disk('local')->files('berkas/'.$this->organisasi->Id.'/thumbnail'));

        $this->actingAs($this->pengguna)->delete(route('kolaborasi.berkas.destroy', $pertama))->assertSessionDoesntHaveErrors();
        Storage::disk('local')->assertExists($kedua->LokasiPenyimpanan);
        Storage::disk('local')->assertExists((string) $kedua->LokasiThumbnail);
        $this->unduh($kedua)->assertOk();

        $this->actingAs($this->pengguna)->delete(route('kolaborasi.berkas.destroy', $kedua))->assertSessionDoesntHaveErrors();
        Storage::disk('local')->assertMissing($kedua->LokasiPenyimpanan);
        Storage::disk('local')->assertMissing((string) $kedua->LokasiThumbnail);
    }

    public function test_hapus_aman_tetap_melihat_rujukan_tanpa_konteks_organisasi(): void
    {
        $csv = str_repeat("Kode,Nama\nAST-1,Pompa\n", 300);
        $pertama = $this->unggah(UploadedFile::fake()->createWithContent('a.csv', $csv));
        $kedua = $this->unggah(UploadedFile::fake()->createWithContent('b.csv', $csv));
        $pertama->delete();

        // Perintah artisan berjalan tanpa konteks organisasi; ScopeOrganisasi akan menyaring 1=0.
        app(KonteksOrganisasi::class)->bersihkan();
        app(PenyimpanBerkas::class)->hapusFisikBilaYatim($pertama);

        Storage::disk('local')->assertExists($kedua->LokasiPenyimpanan);
    }

    public function test_organisasi_lain_tidak_berbagi_salinan_fisik(): void
    {
        $csv = str_repeat("Kode,Nama\nAST-1,Pompa\n", 300);
        $organisasiLain = Organisasi::create(['Kode' => 'ORG-B', 'Nama' => 'Organisasi B']);
        $penggunaLain = $this->buatPengguna($organisasiLain);

        $milikA = $this->unggah(UploadedFile::fake()->createWithContent('a.csv', $csv));
        $milikB = $this->unggah(UploadedFile::fake()->createWithContent('a.csv', $csv), $penggunaLain, $organisasiLain);

        $this->assertSame($milikA->HashSha256, $milikB->HashSha256);
        $this->assertNotSame($milikA->LokasiPenyimpanan, $milikB->LokasiPenyimpanan);
        $this->assertStringStartsWith('berkas/'.$organisasiLain->Id.'/', $milikB->LokasiPenyimpanan);

        $this->actingAs($penggunaLain)->delete(route('kolaborasi.berkas.destroy', $milikB))->assertSessionDoesntHaveErrors();
        Storage::disk('local')->assertExists($milikA->LokasiPenyimpanan);
    }

    public function test_thumbnail_dikirim_dengan_cache_privat_dan_ditolak_tanpa_izin(): void
    {
        $berkas = $this->unggah(UploadedFile::fake()->createWithContent('foto.png', GambarUji::pngTransparan()));
        $orangLain = $this->buatPengguna($this->organisasi, null);

        $respons = $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.thumbnail', $berkas));
        $respons->assertOk();
        $respons->assertHeader('Content-Type', 'image/webp');
        $this->assertStringContainsString('private', (string) $respons->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', (string) $respons->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('local')->get((string) $berkas->LokasiThumbnail), $respons->streamedContent());

        $this->actingAs($orangLain)->get(route('kolaborasi.berkas.thumbnail', $berkas))->assertForbidden();
    }

    public function test_thumbnail_gambar_kecil_mengirim_gambar_asli_dan_bukan_gambar_404(): void
    {
        $kecil = $this->unggah(UploadedFile::fake()->createWithContent('ikon.jpg', GambarUji::jpegDerau(200, 150)));
        $this->assertNull($kecil->LokasiThumbnail);

        $respons = $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.thumbnail', $kecil));
        $respons->assertOk();
        $this->assertSame(Storage::disk('local')->get($kecil->LokasiPenyimpanan), $respons->streamedContent());

        $csv = $this->unggah(UploadedFile::fake()->createWithContent('a.csv', str_repeat("a,b\n", 500)));
        $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.thumbnail', $csv))->assertNotFound();
    }

    private function unggah(UploadedFile $berkas, ?Pengguna $pengguna = null, ?Organisasi $organisasi = null): Berkas
    {
        $pengguna ??= $this->pengguna;
        $organisasi ??= $this->organisasi;

        $this->actingAs($pengguna)
            ->post(route('kolaborasi.berkas.store'), ['Berkas' => $berkas])
            ->assertSessionDoesntHaveErrors();

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($organisasi->Id);

        return Berkas::query()->latest('DibuatPada')->orderByDesc('Id')->firstOrFail();
    }

    private function unduh(Berkas $berkas): TestResponse
    {
        $respons = $this->actingAs($this->pengguna)->get(route('kolaborasi.berkas.unduh', $berkas));
        $respons->assertOk();

        return $respons;
    }

    private function buatPengguna(Organisasi $organisasi, ?string $kodeIzin = 'Pengaturan.Kelola'): Pengguna
    {
        $pengguna = Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pengguna '.uniqid(),
            'Email' => 'pengguna+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        if ($kodeIzin !== null) {
            $konteks = app(KonteksOrganisasi::class);
            $konteks->tetapkan($organisasi->Id);
            $izin = Izin::firstOrCreate(['Kode' => $kodeIzin], ['Nama' => $kodeIzin, 'Modul' => 'Pengaturan']);
            $peran = Peran::create(['Kode' => 'PERAN-'.uniqid(), 'Nama' => 'Peran']);
            PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
            PenggunaPeran::create(['PenggunaId' => $pengguna->Id, 'PeranId' => $peran->Id]);
            $konteks->bersihkan();
        }

        return $pengguna;
    }
}
