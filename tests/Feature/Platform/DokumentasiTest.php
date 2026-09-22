<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Http\Controllers\DokumentasiController;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumentasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(): Pengguna
    {
        $organisasi = Organisasi::create(['Kode' => 'ORG-DOK', 'Nama' => 'Organisasi Dokumentasi']);

        return Pengguna::create([
            'OrganisasiId' => $organisasi->Id,
            'Nama' => 'Pembaca',
            'Email' => 'pembaca+'.uniqid().'@amanpoll.test',
            'KataSandi' => 'rahasia',
            'Status' => 'Aktif',
        ]);
    }

    public function test_dokumentasi_terbuka_untuk_pengguna_tanpa_izin_khusus(): void
    {
        // Sengaja tanpa peran: panduan tidak boleh ikut terkunci izin.
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)
            ->get('/dokumentasi')
            ->assertOk()
            ->assertInertia(fn ($halaman) => $halaman
                ->component('Dokumentasi/Index')
                ->where('halaman', 'pengenalan'));
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/dokumentasi')->assertRedirect('/login');
    }

    public function test_setiap_halaman_yang_terdaftar_dapat_dibuka(): void
    {
        $pengguna = $this->buatPengguna();

        foreach (DokumentasiController::HALAMAN as $slug) {
            $this->actingAs($pengguna)
                ->get("/dokumentasi/{$slug}")
                ->assertOk()
                ->assertInertia(fn ($halaman) => $halaman->where('halaman', $slug));

            app(KonteksOrganisasi::class)->bersihkan();
        }
    }

    public function test_slug_yang_tidak_dikenal_menghasilkan_404(): void
    {
        $pengguna = $this->buatPengguna();

        $this->actingAs($pengguna)->get('/dokumentasi/karangan-sendiri')->assertNotFound();
    }

    /**
     * Daftar slug hidup di dua tempat, dan keduanya harus sama.
     *
     * Sisi PHP menentukan alamat mana yang sah; sisi React menentukan judul dan
     * isinya. Bila salah satu ditambah tanpa yang lain, hasilnya adalah tautan
     * sidebar yang berujung 404, atau alamat sah yang merender halaman kosong.
     */
    public function test_daftar_slug_php_sama_dengan_daftar_slug_react(): void
    {
        $berkas = resource_path('js/features/Dokumentasi/daftar-halaman.ts');
        $this->assertFileExists($berkas);

        preg_match_all("/slug: '([a-z-]+)'/", (string) file_get_contents($berkas), $cocok);
        $slugReact = $cocok[1];

        $this->assertNotEmpty($slugReact, 'Tidak satu pun slug terbaca dari daftar-halaman.ts.');
        $this->assertSame(
            DokumentasiController::HALAMAN,
            $slugReact,
            'Daftar halaman dokumentasi di PHP dan React tidak lagi sama, termasuk urutannya.',
        );
    }
}
