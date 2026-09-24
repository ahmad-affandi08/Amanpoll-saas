<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Sinkronisasi;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** 20.01/20.02 — aset yang membuat aplikasi dapat dipasang. */
final class InstallabilityPwaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manifest_memenuhi_syarat_installability(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['name']);
        $this->assertNotEmpty($manifest['short_name']);

        $ukuran = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $ukuran);
        $this->assertContains('512x512', $ukuran);

        $maskable = array_filter($manifest['icons'], fn (array $ikon): bool => ($ikon['purpose'] ?? '') === 'maskable');
        $this->assertNotEmpty($maskable, 'Manifest harus menyediakan ikon maskable.');

        foreach ($manifest['icons'] as $ikon) {
            $this->assertFileExists(public_path(ltrim($ikon['src'], '/')));
        }
    }

    public function test_service_worker_dan_halaman_cadangan_offline_tersedia(): void
    {
        // Keduanya disajikan langsung oleh web server sebagai berkas statis (aturan `!-f` pada .htaccess).
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $halamanOffline = (string) file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('Perangkat sedang tidak terhubung', $halamanOffline);
        $this->assertStringNotContainsString('@vite', $halamanOffline, 'Halaman cadangan tidak boleh bergantung pada bundel build.');

        $blade = (string) file_get_contents(resource_path('views/app.blade.php'));
        $this->assertStringContainsString('rel="manifest"', $blade);
    }

    public function test_service_worker_tidak_menyimpan_respons_yang_berisi_data_tenant(): void
    {
        $sw = (string) file_get_contents(public_path('sw.js'));

        // Hanya permintaan GET yang boleh diproses, dan hanya path aset statis yang boleh masuk cache runtime.
        $this->assertStringContainsString("permintaan.method !== 'GET'", $sw);
        $this->assertStringContainsString('POLA_ASET_STATIS', $sw);

        // Hanya kerangka ruang kerja teknisi yang boleh disimpan dari jalur navigasi.
        $this->assertStringContainsString("HALAMAN_OFFLINE_DIIZINKAN = '/offline/teknisi'", $sw);
        $this->assertStringContainsString('url.pathname === HALAMAN_OFFLINE_DIIZINKAN', $sw);
        $kode = (string) preg_replace('#/\*.*?\*/|//[^\n]*#s', '', $sw);
        foreach (['/offline/paket', '/offline/antrian', '/api/'] as $endpointData) {
            $this->assertStringNotContainsString(
                $endpointData,
                $kode,
                'Service worker tidak boleh menyentuh endpoint data sama sekali.',
            );
        }

        // Cache runtime dikunci per organisasi + pengguna (20.02).
        $this->assertStringContainsString('AWALAN_RUNTIME', $sw);
        $this->assertStringContainsString('kunciKonteks', $sw);
    }

    /** Mode Lapangan menggantikan ruang kerja teknisi; jalur lamanya tetap hidup lewat pengalihan (PRD 8.20). */
    public function test_jalur_teknisi_offline_dialihkan_ke_mode_lapangan(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get('/offline/teknisi')
            ->assertRedirect('/lapangan');
    }

    public function test_halaman_teknisi_offline_menolak_tamu(): void
    {
        $this->get('/offline/teknisi')->assertRedirect('/login');
    }

    public function test_melepas_perangkat_membuang_penanda_dan_menonaktifkan_perangkat(): void
    {
        $pengguna = $this->buatPengguna();
        $amplop = ['IdentitasPerangkat' => 'perangkat-logout-01', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

        $this->actingAs($pengguna)->postJson('/offline/paket', $amplop)->assertOk();
        $this->assertGreaterThan(0, PenandaSinkronisasi::query()->withoutGlobalScopes()->count());

        $this->actingAs($pengguna)
            ->postJson('/offline/perangkat/lepas', $amplop)
            ->assertOk()
            ->assertJson(['Dilepas' => true]);

        $perangkat = PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $pengguna->Id)->firstOrFail();
        $this->assertSame('Nonaktif', $perangkat->Status);
        $this->assertSame(0, PenandaSinkronisasi::query()->withoutGlobalScopes()
            ->where('PerangkatPenggunaId', $perangkat->Id)->count());
    }

    public function test_perangkat_yang_sama_tidak_didaftarkan_dua_kali(): void
    {
        $pengguna = $this->buatPengguna();
        $amplop = ['IdentitasPerangkat' => 'perangkat-tetap-01', 'NamaPerangkat' => 'Ponsel', 'Platform' => 'Android'];

        $pertama = $this->actingAs($pengguna)->postJson('/offline/paket', $amplop);
        $kedua = $this->actingAs($pengguna)->postJson('/offline/paket', $amplop);

        $this->assertSame($pertama->json('Perangkat.Id'), $kedua->json('Perangkat.Id'));
        $this->assertSame(1, PerangkatPengguna::query()->withoutGlobalScopes()
            ->where('PenggunaId', $pengguna->Id)->count());
    }

    public function test_ringkasan_sinkronisasi_hanya_menghitung_perangkat_milik_pengguna(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->getJson('/offline/ringkasan')
            ->assertOk()
            ->assertJson(['Menunggu' => 0, 'Konflik' => 0, 'Gagal' => 0]);
    }

    private function buatPengguna(): Pengguna
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-PWA-'.uniqid(), 'Nama' => 'Organisasi PWA']);
        app(KonteksOrganisasi::class)->tetapkan($organisasi->Id);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Teknisi PWA',
            'Email' => uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }
}
