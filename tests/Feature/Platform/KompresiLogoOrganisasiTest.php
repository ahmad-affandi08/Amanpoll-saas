<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\Actions\UnggahLogoOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use App\Shared\Infrastructure\Ekspor\LogoKopEkspor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Dukungan\GambarUji;
use Tests\TestCase;

/**
 * Logo organisasi lewat mesin kompresi (PRD 11.1). Disk `public` dilayani web
 * server apa adanya, jadi yang tersimpan di sana harus gambar yang dapat
 * langsung ditampilkan peramban, tidak pernah gzip.
 */
final class KompresiLogoOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    private Organisasi $organisasi;

    private Pengguna $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public', ['url' => (string) config('filesystems.disks.public.url')]);
        $this->organisasi = Organisasi::create(['Kode' => 'ORG-A', 'Nama' => 'Organisasi A', 'ZonaWaktu' => 'Asia/Jakarta']);
        $this->admin = $this->buatAdmin();
    }

    public function test_logo_png_disimpan_sebagai_webp_yang_tetap_terbaca_kop_pdf(): void
    {
        $asli = GambarUji::pngTransparan();

        $this->unggah(UploadedFile::fake()->createWithContent('logo.png', $asli))->assertSessionDoesntHaveErrors();

        $this->organisasi->refresh();
        $path = $this->pathLogo();
        $isi = (string) Storage::disk('public')->get($path);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('organisasi/'.$this->organisasi->Id.'/', $path);
        $this->assertSame('RIFF', substr($isi, 0, 4));
        $this->assertSame('WEBP', substr($isi, 8, 4));
        $this->assertLessThan(strlen($asli), strlen($isi));
        $this->assertStringStartsWith('data:image/webp;base64,', (string) LogoKopEkspor::dataUri($this->organisasi->LogoUrl));
    }

    public function test_mengganti_logo_menghapus_logo_lama(): void
    {
        $this->unggah(UploadedFile::fake()->createWithContent('logo.png', GambarUji::pngTransparan()));
        $lama = $this->pathLogo();

        $this->unggah(UploadedFile::fake()->createWithContent('logo-baru.png', GambarUji::pngTransparan(600, 300)));

        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($this->pathLogo());
    }

    /**
     * Web server menyajikan disk `public` tanpa `Content-Encoding`, jadi apa pun
     * yang akan di-gzip mesin disimpan apa adanya di sana, bukan sebagai `.gz`.
     */
    public function test_disk_publik_tidak_pernah_menerima_gzip(): void
    {
        $isi = str_repeat("Logo organisasi dalam teks biasa.\n", 200);

        app(UnggahLogoOrganisasi::class)->jalankan($this->organisasi, UploadedFile::fake()->createWithContent('logo.txt', $isi));

        $path = $this->pathLogo();
        $this->assertStringEndsNotWith('.gz', $path);
        $this->assertSame($isi, Storage::disk('public')->get($path));
    }

    /** Kegagalan kompresi tidak menggagalkan unggahan: logonya tersimpan apa adanya dan tercatat di log. */
    public function test_logo_rusak_tetap_tersimpan_apa_adanya(): void
    {
        Log::spy();
        $utuh = GambarUji::jpeg(600, 400);
        $rusak = substr($utuh, 0, (int) (strlen($utuh) * 0.6));

        $this->unggah(UploadedFile::fake()->createWithContent('logo.jpg', $rusak))->assertSessionDoesntHaveErrors();

        $path = $this->pathLogo();
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame($rusak, Storage::disk('public')->get($path));
        Log::shouldHaveReceived('warning')->withArgs(fn (string $pesan): bool => str_contains($pesan, 'Kompresi berkas gagal'))->once();
    }

    /** @return TestResponse<Response> */
    private function unggah(UploadedFile $berkas): TestResponse
    {
        return $this->actingAs($this->admin)->post('/platform/organisasi/logo', ['Logo' => $berkas]);
    }

    private function pathLogo(): string
    {
        $this->organisasi->refresh();

        return str_replace(Storage::disk('public')->url(''), '', (string) $this->organisasi->LogoUrl);
    }

    private function buatAdmin(): Pengguna
    {
        $admin = Pengguna::create([
            'OrganisasiId' => $this->organisasi->Id,
            'Nama' => 'Admin',
            'Email' => 'admin+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);

        $konteks = app(KonteksOrganisasi::class);
        $konteks->tetapkan($this->organisasi->Id);
        $izin = Izin::firstOrCreate(['Kode' => 'Pengaturan.Kelola'], ['Nama' => 'Kelola Pengaturan', 'Modul' => 'Sistem']);
        $peran = Peran::create(['Kode' => 'ADMIN', 'Nama' => 'Administrator']);
        PeranIzin::create(['PeranId' => $peran->Id, 'IzinId' => $izin->Id]);
        PenggunaPeran::create(['PenggunaId' => $admin->Id, 'PeranId' => $peran->Id]);
        $konteks->bersihkan();

        return $admin;
    }
}
