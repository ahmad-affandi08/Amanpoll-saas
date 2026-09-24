<?php

declare(strict_types=1);

namespace Tests\Feature\Lapangan;

use App\Domain\Aset\Application\Actions\TambahFotoAset;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Application\Actions\LampirkanBerkas;
use App\Domain\Kolaborasi\Application\Services\PenyimpanBerkas;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Foto aset di Mode Lapangan (PRD 8.4 "Foto Aset", 8.20; TASK 42.02): foto utama di
 * layar teknisi dan pelapor, hak teknisi menambah foto dari HP (termasuk kiriman
 * tertunda dari antrean HP), grid foto tiket yang memakai thumbnail, dan paket offline.
 */
final class TeknisiFotoAsetTest extends KasusPelapor
{
    private Lokasi $ruang;

    private Aset $aset;

    private Pengguna $teknisi;

    private string $urlFoto;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->ruang = $this->lokasi('Ruang Panel');
        $this->aset = $this->aset('Genset Utama', $this->ruang, 'Rusak');
        $this->teknisi = $this->penggunaDenganPeran(['TEKNISI']);
        $berkasId = $this->tambahFoto($this->aset);
        $this->urlFoto = "/kolaborasi/berkas/{$berkasId}/thumbnail";
    }

    public function test_layar_aset_teknisi_menampilkan_foto_utama_dan_hak_menambah_foto(): void
    {
        $tanpaFoto = $this->aset('Pompa Transfer', $this->ruang);
        $tiket = $this->tiket($this->teknisi, $this->aset, 'Dikerjakan');

        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/pindai?aset={$this->aset->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('asetDitemukan.FotoUtamaThumbnailUrl', $this->urlFoto)
                ->where('asetDitemukan.BolehTambahFoto', true));
        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/pindai?aset={$tanpaFoto->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('asetDitemukan.FotoUtamaThumbnailUrl', null)
                ->where('asetDitemukan.BolehTambahFoto', false));
        $this->actingAs($this->teknisi)->get('/lapangan/teknisi/aset')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('aset.0.Id', $this->aset->Id)
                ->where('aset.0.FotoUtamaThumbnailUrl', $this->urlFoto));
        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/aset/{$this->aset->Id}/riwayat")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('aset.FotoUtamaThumbnailUrl', $this->urlFoto));
        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('tiket.Aset.FotoUtamaThumbnailUrl', $this->urlFoto));
        // Pengguna lapangan murni memuat thumbnailnya sebagai `<img>`, tanpa dialihkan ke Mode Lapangan.
        $this->actingAs($this->teknisi)->get($this->urlFoto)->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    /**
     * Kiriman tertunda dari HP memakai endpoint yang sama (`Foto[]`, JSON). Selagi
     * penugasan aktif ia diterima; sesudah penugasan berakhir ia ditolak dengan pesan
     * yang ditampilkan layar, bukan hilang diam-diam.
     */
    public function test_foto_dari_antrean_hp_diterima_selama_ditugaskan_dan_ditolak_sesudahnya(): void
    {
        $asetTanpaFoto = $this->aset('Panel Distribusi', $this->ruang);
        $tiket = $this->tiket($this->teknisi, $asetTanpaFoto, 'Diterima');

        $this->actingAs($this->teknisi)
            ->post(route('aset.foto.store', $asetTanpaFoto), ['Foto' => [UploadedFile::fake()->image('FotoAset-2026.webp', 800, 600)]], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('FotoUtamaThumbnailUrl', fn (string $url): bool => str_starts_with($url, '/kolaborasi/berkas/'));
        $this->assertSame(1, $this->jumlahFoto($asetTanpaFoto));

        $this->dalamOrganisasi(fn () => PenugasanPerintahKerja::query()->where('PerintahKerjaId', $tiket->Id)->update(['Status' => 'Selesai']));

        $this->actingAs($this->teknisi)
            ->post(route('aset.foto.store', $asetTanpaFoto), ['Foto' => [UploadedFile::fake()->image('FotoAset-2027.webp', 800, 600)]], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('pesan', fn (string $pesan): bool => str_contains($pesan, 'selama ditugaskan pada perintah kerja aktif'));
        $this->assertSame(1, $this->jumlahFoto($asetTanpaFoto));
    }

    public function test_pelapor_melihat_foto_utama_di_daftar_aset_dan_langkah_alat_ditemukan(): void
    {
        $pelapor = $this->pelaporDi($this->ruang);

        $this->actingAs($pelapor)->get('/lapangan/pelapor/aset')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('aset.0.Id', $this->aset->Id)
                ->where('aset.0.FotoUtamaThumbnailUrl', $this->urlFoto));
        $this->actingAs($pelapor)->get("/lapangan/pelapor/lapor?aset={$this->aset->Id}")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('asetTerpilih.FotoUtamaThumbnailUrl', $this->urlFoto)
                ->where('aset.0.FotoUtamaThumbnailUrl', $this->urlFoto));
        $this->actingAs($pelapor)->get($this->urlFoto)->assertOk();
    }

    public function test_grid_foto_tiket_teknisi_memakai_thumbnail_bukan_unduhan_penuh(): void
    {
        $tiket = $this->tiket($this->teknisi, $this->aset, 'Dikerjakan');
        $berkasId = $this->dalamOrganisasi(function () use ($tiket): string {
            $berkas = app(PenyimpanBerkas::class)->simpanUnggahan(UploadedFile::fake()->image('sebelum.jpg', 900, 700), $this->teknisi->Id);
            app(LampirkanBerkas::class)->jalankan('PerintahKerja', $tiket->Id, $berkas->Id, 'FotoSebelum', null, $this->teknisi->Id);

            return $berkas->Id;
        });

        $this->actingAs($this->teknisi)->get("/lapangan/teknisi/tugas/{$tiket->Id}/kerjakan")
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman
                ->where('foto.0.Url', "/kolaborasi/berkas/{$berkasId}/thumbnail")
                ->where('foto.0.UrlUnduh', "/kolaborasi/berkas/{$berkasId}/unduh"));
    }

    public function test_paket_offline_membawa_thumbnail_foto_utama_dan_service_worker_tidak_menyimpannya(): void
    {
        $this->tiket($this->teknisi, $this->aset, 'Dikerjakan');

        $paket = $this->actingAs($this->teknisi)
            ->postJson(route('offline.paket'), ['IdentitasPerangkat' => 'hp-foto-aset', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'])
            ->assertOk();
        $this->assertSame($this->urlFoto, collect($paket->json('Paket.Aset'))->firstWhere('Id', $this->aset->Id)['FotoUtamaThumbnailUrl'] ?? null);

        // Thumbnail berkas privat tidak masuk cache runtime service worker; tanpa sinyal layar memakai ikon 3D.
        $sw = (string) file_get_contents(public_path('sw.js'));
        $this->assertSame(1, preg_match('/const POLA_ASET_STATIS = \[(.*?)\];/', $sw, $cocok));
        // Tiap unsur adalah literal regex JS (`/^\/build\//`); tanpa garis miring pembuka dan penutup ia regex PCRE yang sama.
        $pola = array_map(fn (string $satu): string => substr(trim($satu), 1, -1), explode(',', $cocok[1]));
        $this->assertContains('^\/images\/', $pola);
        foreach ($pola as $satu) {
            $this->assertSame(0, preg_match('~'.$satu.'~', $this->urlFoto), "Pola {$satu} tidak boleh mencakup thumbnail berkas.");
        }
    }

    private function tambahFoto(Aset $aset): string
    {
        return $this->dalamOrganisasi(fn (): string => app(TambahFotoAset::class)->jalankan(
            $aset,
            [UploadedFile::fake()->image('genset.jpg', 800, 600)],
            null,
        )[0]->Id);
    }

    private function jumlahFoto(Aset $aset): int
    {
        return $this->dalamOrganisasi(fn (): int => app(GaleriFotoAset::class)->jumlah($aset));
    }

    private function tiket(Pengguna $teknisi, Aset $aset, string $status): PerintahKerja
    {
        return $this->dalamOrganisasi(function () use ($teknisi, $aset, $status): PerintahKerja {
            $tiket = PerintahKerja::create([
                'Nomor' => 'PK-LAP-'.uniqid(), 'Jenis' => 'Korektif', 'Judul' => 'Genset mati total',
                'Prioritas' => 'Tinggi', 'Status' => $status, 'BatasPenyelesaianPada' => now()->addHours(3),
            ]);
            PerintahKerjaAset::create(['PerintahKerjaId' => $tiket->Id, 'AsetId' => $aset->Id, 'Utama' => true]);
            PenugasanPerintahKerja::create([
                'PerintahKerjaId' => $tiket->Id, 'PenggunaId' => $teknisi->Id,
                'Status' => $status === 'Ditugaskan' ? 'Ditugaskan' : 'Diterima', 'DitugaskanPada' => now()->subHour(),
            ]);

            return $tiket;
        });
    }
}
