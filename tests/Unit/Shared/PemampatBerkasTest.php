<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Infrastructure\Kompresi\HasilPemampatan;
use App\Shared\Infrastructure\Kompresi\MetodeKompresi;
use App\Shared\Infrastructure\Kompresi\PemampatBerkas;
use App\Shared\Infrastructure\Kompresi\PembersihMetadataGambar;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Dukungan\GambarUji;
use Tests\TestCase;

/**
 * Aturan mesin kompresi PRD 11.1 pada tingkat berkas: yang disimpan apa adanya,
 * yang dikodekan ulang, dan yang di-gzip. Jalur simpan, unduh, dan berbagi
 * salinan diuji di tests/Feature/Kolaborasi/KompresiBerkasTest.
 */
class PemampatBerkasTest extends TestCase
{
    /** @var list<string> */
    private array $berkasUji = [];

    /** @var list<HasilPemampatan> */
    private array $hasil = [];

    protected function tearDown(): void
    {
        foreach ($this->hasil as $hasil) {
            $hasil->bersihkan();
        }
        foreach ($this->berkasUji as $lokasi) {
            if (is_file($lokasi)) {
                unlink($lokasi);
            }
        }

        parent::tearDown();
    }

    public function test_gif_beranimasi_disimpan_apa_adanya(): void
    {
        $isi = GambarUji::gifAnimasi();
        $hasil = $this->pampatkan($isi, 'image/gif', 'animasi.gif');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
        $this->assertSame($isi, file_get_contents($hasil->lokasiIsi));
        $this->assertSame('image/gif', $hasil->jenisMime);
        $this->assertNull($hasil->lokasiThumbnail);
    }

    /**
     * Kotak merah berada di pojok kiri atas gambar tersimpan; sesudah diluruskan
     * ia harus berada di pojok yang ditunjuk Orientation.
     *
     * @return array<string, array{0: int, 1: bool, 2: string}>
     */
    public static function orientasi(): array
    {
        return [
            '3 diputar 180' => [3, false, 'kanan-bawah'],
            '6 diputar 90 searah jarum jam' => [6, true, 'kanan-atas'],
            '8 diputar 90 berlawanan jarum jam' => [8, true, 'kiri-bawah'],
            '2 dicerminkan' => [2, false, 'kanan-atas'],
        ];
    }

    #[DataProvider('orientasi')]
    public function test_orientasi_exif_diluruskan_walau_hasilnya_lebih_besar(int $orientasi, bool $bertukarSisi, string $pojokMerah): void
    {
        $asli = GambarUji::denganExif(GambarUji::jpeg(300, 200, 60), $orientasi, denganGps: false);
        $hasil = $this->pampatkan($asli, 'image/jpeg', 'miring.jpg');

        $this->assertSame(MetodeKompresi::GambarUlang, $hasil->metode);
        $gambar = GambarUji::gambarDari((string) file_get_contents($hasil->lokasiIsi));
        [$lebar, $tinggi] = [imagesx($gambar), imagesy($gambar)];
        $this->assertSame($bertukarSisi ? [200, 300] : [300, 200], [$lebar, $tinggi]);

        $titik = [
            'kiri-atas' => [5, 5],
            'kanan-atas' => [$lebar - 6, 5],
            'kiri-bawah' => [5, $tinggi - 6],
            'kanan-bawah' => [$lebar - 6, $tinggi - 6],
        ];
        foreach ($titik as $nama => [$x, $y]) {
            [$merah, $hijau] = GambarUji::piksel($gambar, $x, $y);
            $this->assertSame($nama === $pojokMerah, $merah > 200 && $hijau < 60, "Pojok {$nama}");
        }
    }

    public function test_gambar_yang_webp_nya_lebih_besar_disimpan_asli_tanpa_metadata(): void
    {
        $tanpaExif = GambarUji::jpegDerau();
        $asli = GambarUji::denganExif($tanpaExif, 1);
        $hasil = $this->pampatkan($asli, 'image/jpeg', 'derau.jpg');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
        $this->assertSame('image/jpeg', $hasil->jenisMime);
        $this->assertSame('jpg', $hasil->ekstensi);

        $tersimpan = (string) file_get_contents($hasil->lokasiIsi);
        $this->assertStringContainsString('Exif', $asli);
        $this->assertStringNotContainsString('Exif', $tersimpan);
        $this->assertStringNotContainsString('gd-jpeg', $tersimpan, 'Komentar JPEG ikut dibuang.');
        // Data piksel (mulai SOS) tidak disentuh sama sekali.
        $this->assertSame(strstr($asli, "\xFF\xDA"), strstr($tersimpan, "\xFF\xDA"));
        $this->assertSame(strlen($tersimpan), $hasil->ukuranTersimpan);
        $this->assertSame(strlen($asli), $hasil->ukuranAsli);
    }

    public function test_pembersih_membuang_chunk_teks_png_tanpa_menyentuh_piksel(): void
    {
        $png = GambarUji::pngTransparan(40, 30);
        // Chunk tEXt disisipkan sesudah IHDR (8 + 25 byte).
        $teks = "Comment\0lokasi rahasia";
        $chunk = pack('N', strlen($teks)).'tEXt'.$teks.pack('N', crc32('tEXt'.$teks));
        $lokasi = $this->berkasUji(substr($png, 0, 33).$chunk.substr($png, 33));

        $bersih = (new PembersihMetadataGambar)->bersihkan($lokasi, IMAGETYPE_PNG);

        $this->assertNotNull($bersih);
        $this->berkasUji[] = $bersih;
        $this->assertSame($png, file_get_contents($bersih));
        $this->assertNull((new PembersihMetadataGambar)->bersihkan($bersih, IMAGETYPE_PNG), 'Tanpa metadata: tidak ada salinan.');
    }

    public function test_pembersih_membuang_exif_webp_dan_mematikan_benderanya(): void
    {
        $gambar = GambarUji::gambarDari(GambarUji::pngTransparan(40, 30));
        ob_start();
        imagewebp($gambar, null, 80);
        $webp = (string) ob_get_clean();
        // GD menulis VP8X (ada alfa); bendera EXIF dinyalakan dan chunk EXIF ditambahkan di akhir.
        $this->assertSame('VP8X', substr($webp, 12, 4));
        $benderaAsli = ord($webp[20]);
        $webp[20] = chr($benderaAsli | 0x08);
        $exif = "Exif\0\0GPS-rahasia";
        $chunkExif = 'EXIF'.pack('V', strlen($exif)).$exif.(strlen($exif) % 2 === 1 ? "\0" : '');
        $isi = substr($webp, 8).$chunkExif;
        $lokasi = $this->berkasUji('RIFF'.pack('V', strlen($isi)).$isi);

        $bersih = (new PembersihMetadataGambar)->bersihkan($lokasi, IMAGETYPE_WEBP);

        $this->assertNotNull($bersih);
        $this->berkasUji[] = $bersih;
        $tersimpan = (string) file_get_contents($bersih);
        $this->assertStringNotContainsString('GPS-rahasia', $tersimpan);
        $this->assertSame(strlen($tersimpan) - 8, unpack('V', substr($tersimpan, 4, 4))[1]);
        $this->assertSame($benderaAsli, ord($tersimpan[20]), 'Bendera EXIF mati, bendera lain tetap.');
        $this->assertSame(substr($webp, 0, 20), substr($tersimpan, 0, 20));
        $this->assertSame([40, 30], array_slice((array) getimagesize($bersih), 0, 2));
    }

    public function test_format_yang_tidak_dibaca_server_disimpan_apa_adanya_tanpa_log(): void
    {
        Log::spy();
        $isi = "\x00\x00\x00\x18ftypheic".str_repeat("\x00", 200);
        $hasil = $this->pampatkan($isi, 'image/heic', 'foto.heic');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
        $this->assertSame($isi, file_get_contents($hasil->lokasiIsi));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_gambar_di_atas_batas_piksel_tidak_didekode(): void
    {
        config(['amanpoll.kompresi.gambar.piksel_maks' => 1000]);
        $asli = GambarUji::jpeg(300, 200);

        $hasil = $this->pampatkan($asli, 'image/jpeg', 'besar.jpg');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
        $this->assertSame($asli, file_get_contents($hasil->lokasiIsi));
        $this->assertNull($hasil->lokasiThumbnail);
    }

    public function test_teks_kecil_yang_gzipnya_lebih_besar_disimpan_apa_adanya(): void
    {
        $hasil = $this->pampatkan('a,b', 'text/csv', 'kecil.csv');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
        $this->assertSame('csv', $hasil->ekstensi);
    }

    public function test_json_di_gzip_level_konfigurasi_dan_dapat_dibuka_kembali(): void
    {
        $json = (string) json_encode(array_fill(0, 500, ['Kode' => 'AST-0001', 'Nama' => 'Pompa infus']));
        $hasil = $this->pampatkan($json, 'application/json', 'data.json');

        $this->assertSame(MetodeKompresi::Gzip, $hasil->metode);
        $this->assertSame('json.gz', $hasil->ekstensi);
        $this->assertSame('application/json', $hasil->jenisMime);
        $this->assertSame($json, gzdecode((string) file_get_contents($hasil->lokasiIsi)));
        $this->assertSame(strlen($json), $hasil->ukuranUnduhan());
    }

    public function test_kompresi_nonaktif_menyimpan_semua_apa_adanya(): void
    {
        config(['amanpoll.kompresi.aktif' => false]);
        $csv = str_repeat("Kode,Nama\nAST-1,Pompa\n", 200);

        $hasil = $this->pampatkan($csv, 'text/csv', 'data.csv');

        $this->assertSame(MetodeKompresi::Tidak, $hasil->metode);
    }

    private function berkasUji(string $isi): string
    {
        $lokasi = (string) tempnam(sys_get_temp_dir(), 'uji-kompresi-');
        file_put_contents($lokasi, $isi);
        $this->berkasUji[] = $lokasi;

        return $lokasi;
    }

    private function pampatkan(string $isi, string $jenisMime, string $nama): HasilPemampatan
    {
        $lokasi = $this->berkasUji($isi);

        $hasil = app(PemampatBerkas::class)->pampatkan($lokasi, $jenisMime, $nama);
        $this->hasil[] = $hasil;

        return $hasil;
    }
}
