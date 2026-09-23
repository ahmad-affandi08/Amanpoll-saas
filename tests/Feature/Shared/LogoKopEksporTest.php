<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Shared\Infrastructure\Ekspor\LogoKopEkspor;
use App\Shared\Infrastructure\Ekspor\PenulisEksporPdf;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Logo kop PDF.
 *
 * `Organisasi.LogoUrl` diisi tenant. Yang dijaga di sini adalah bahwa hanya
 * berkas milik disk aplikasi sendiri yang pernah dibaca: URL ke host lain tidak
 * boleh berubah menjadi permintaan keluar atas nama server saat ekspor PDF
 * dicetak.
 */
final class LogoKopEksporTest extends TestCase
{
    /** PNG 1x1 yang sah, cukup untuk membuktikan berkasnya terbaca sebagai gambar. */
    private const PNG_SATU_PIKSEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        // URL disk ikut disalin: awalan itulah yang dipakai memutuskan apakah
        // sebuah LogoUrl menunjuk ke disk aplikasi.
        Storage::fake('public', ['url' => (string) config('filesystems.disks.public.url')]);
    }

    private function simpanLogo(string $path, string $isi): string
    {
        Storage::disk('public')->put($path, $isi);

        return Storage::disk('public')->url($path);
    }

    public function test_logo_di_disk_publik_menjadi_data_uri(): void
    {
        $url = $this->simpanLogo('organisasi/abc/logo.png', (string) base64_decode(self::PNG_SATU_PIKSEL, true));

        $this->assertSame(
            'data:image/png;base64,'.self::PNG_SATU_PIKSEL,
            LogoKopEkspor::dataUri($url),
        );
    }

    public function test_url_jarak_jauh_tidak_menghasilkan_apa_apa(): void
    {
        $this->simpanLogo('organisasi/abc/logo.png', (string) base64_decode(self::PNG_SATU_PIKSEL, true));

        $this->assertNull(LogoKopEkspor::dataUri('https://penyerang.test/logo.png'));
        $this->assertNull(LogoKopEkspor::dataUri('http://169.254.169.254/latest/meta-data/'));
        $this->assertNull(LogoKopEkspor::dataUri('file:///etc/passwd'));
    }

    public function test_logo_yang_berkasnya_sudah_tidak_ada_dilewati(): void
    {
        $url = Storage::disk('public')->url('organisasi/abc/hilang.png');

        $this->assertNull(LogoKopEkspor::dataUri($url));
    }

    public function test_berkas_yang_bukan_gambar_dilewati(): void
    {
        $url = $this->simpanLogo('organisasi/abc/logo.png', '<?php echo "bukan gambar";');

        $this->assertNull(LogoKopEkspor::dataUri($url));
    }

    public function test_path_yang_keluar_dari_disknya_ditolak(): void
    {
        $awalan = Storage::disk('public')->url('');

        $this->assertNull(LogoKopEkspor::dataUri($awalan.'../../../../etc/passwd'));
        // Disandikan persen: pemeriksaan yang dilakukan sebelum didekode akan meloloskannya.
        $this->assertNull(LogoKopEkspor::dataUri($awalan.'%2e%2e/%2e%2e/etc/passwd'));
    }

    /**
     * Batas ukurannya nyata, bukan hiasan: logo besar disalin utuh ke dalam
     * setiap PDF sebagai base64, jadi berkas 5 MB menjadi ekspor yang tidak
     * dapat dikirim lewat surel oleh siapa pun yang memintanya.
     */
    public function test_logo_yang_melewati_batas_ukuran_dilewati(): void
    {
        $gambar = (string) base64_decode(self::PNG_SATU_PIKSEL, true);
        $url = $this->simpanLogo('organisasi/abc/besar.png', $gambar.str_repeat("\0", 512 * 1024));

        $this->assertNull(LogoKopEkspor::dataUri($url));
    }

    public function test_logo_kosong_atau_belum_disetel_dilewati(): void
    {
        $this->assertNull(LogoKopEkspor::dataUri(null));
        $this->assertNull(LogoKopEkspor::dataUri(''));
        $this->assertNull(LogoKopEkspor::dataUri('   '));
    }

    /**
     * Lapis kedua, di penulisnya sendiri: sekalipun ada yang mengoper URL
     * mentah ke kop PDF, URL itu tidak pernah menjadi <img src>. Tanpa penjaga
     * ini yang menahan hanyalah isRemoteEnabled yang mati -- setelan yang dapat
     * dihidupkan orang lain tanpa menyadari akibatnya.
     *
     * Diperiksa pada kop yang dirender, bukan pada byte PDF-nya: gambar yang
     * ditolak tidak meninggalkan jejak di hasil, sehingga PDF-nya tampak sama
     * saja apakah penjaganya ada atau tidak.
     */
    public function test_penulis_pdf_menolak_logo_yang_bukan_data_uri(): void
    {
        $kop = (new PenulisEksporPdf('https://penyerang.test/logo.png'))->kop('Daftar Aset', 'RS Uji');

        $this->assertStringNotContainsString('<img', $kop);
        $this->assertStringNotContainsString('penyerang.test', $kop);
        $this->assertStringContainsString('RS Uji', $kop);
    }

    public function test_penulis_pdf_memasang_logo_yang_berupa_data_uri(): void
    {
        $dataUri = 'data:image/png;base64,'.self::PNG_SATU_PIKSEL;

        $kop = (new PenulisEksporPdf($dataUri))->kop('Daftar Aset', 'RS Uji');

        $this->assertStringContainsString('<img src="'.$dataUri.'"', $kop);
    }
}
