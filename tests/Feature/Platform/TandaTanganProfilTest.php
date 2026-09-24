<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\Dukungan\GambarUji;
use Tests\Feature\Lapangan\KasusLapangan;

/**
 * Tanda tangan tersimpan di profil pengguna (PRD 8.22, TASK 43.01).
 */
final class TandaTanganProfilTest extends KasusLapangan
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_pengguna_menyimpan_tanda_tangan_ke_profilnya(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($pengguna)
            ->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])
            ->assertOk();

        $pengguna->refresh();
        $this->assertNotNull($pengguna->TandaTanganBerkasId);

        $berkas = $this->dalamOrganisasi(fn () => Berkas::query()->find($pengguna->TandaTanganBerkasId));
        $this->assertNotNull($berkas);
        $this->assertSame($pengguna->Id, $berkas->DiunggahOleh);
        $this->assertSame(1, DB::table('CatatanAudit')->where('Aksi', 'Pengguna.TandaTanganDisimpan')->where('EntitasId', $pengguna->Id)->count());

        $this->actingAs($pengguna)
            ->get('/platform/profil')
            ->assertInertia(fn (AssertableInertia $halaman) => $halaman->where('auth.pengguna.PunyaTandaTangan', true));
    }

    public function test_mengganti_tanda_tangan_tidak_menghapus_berkas_lama(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($pengguna)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])->assertOk();
        $lama = $pengguna->refresh()->TandaTanganBerkasId;

        $this->actingAs($pengguna)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan(520)])->assertOk();
        $baru = $pengguna->refresh()->TandaTanganBerkasId;

        $this->assertNotSame($lama, $baru);
        $this->assertSame(1, DB::table('Berkas')->where('Id', $lama)->whereNull('DihapusPada')->count());
    }

    public function test_menghapus_tanda_tangan_hanya_melepas_dari_profil(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);
        $this->actingAs($pengguna)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])->assertOk();
        $berkasId = $pengguna->refresh()->TandaTanganBerkasId;

        $this->actingAs($pengguna)->deleteJson('/profil/tanda-tangan')->assertOk();

        $this->assertNull($pengguna->refresh()->TandaTanganBerkasId);
        $this->assertSame(1, DB::table('Berkas')->where('Id', $berkasId)->whereNull('DihapusPada')->count());
        $this->assertSame(1, DB::table('CatatanAudit')->where('Aksi', 'Pengguna.TandaTanganDihapus')->count());
        $this->actingAs($pengguna)->get('/profil/tanda-tangan')->assertNotFound();
    }

    public function test_hanya_pemilik_yang_melihat_tanda_tangannya(): void
    {
        $pemilik = $this->penggunaMeja(['Aset.Lihat']);
        $lain = $this->penggunaMeja(['Aset.Lihat']);
        $this->actingAs($pemilik)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])->assertOk();

        $respons = $this->actingAs($pemilik)->get('/profil/tanda-tangan');
        $respons->assertOk();
        $this->assertStringStartsWith('image/', (string) $respons->headers->get('Content-Type'));

        // Tidak ada parameter pengguna: pengguna lain hanya bisa meminta miliknya sendiri, yang belum ada.
        $this->actingAs($lain)->get('/profil/tanda-tangan')->assertNotFound();
    }

    public function test_menolak_berkas_bukan_gambar_dan_yang_terlalu_besar(): void
    {
        $pengguna = $this->penggunaMeja(['Aset.Lihat']);

        $this->actingAs($pengguna)
            ->postJson('/profil/tanda-tangan', ['TandaTangan' => UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('TandaTangan');

        $this->actingAs($pengguna)
            ->postJson('/profil/tanda-tangan', ['TandaTangan' => UploadedFile::fake()->image('ttd.png')->size(600)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('TandaTangan');

        $this->assertNull($pengguna->refresh()->TandaTanganBerkasId);
    }

    public function test_pengguna_lapangan_murni_dapat_mengelola_tanda_tangan_tanpa_dialihkan(): void
    {
        $pelapor = $this->penggunaDenganPeran(['PELAPOR']);

        $this->actingAs($pelapor)->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])->assertOk();

        // Gambar dimuat `<img>` (bukan permintaan JSON) dari halaman Akun Mode Lapangan.
        $this->actingAs($pelapor)->get('/profil/tanda-tangan')->assertOk();
    }

    public function test_tamu_tidak_dapat_mengakses_tanda_tangan(): void
    {
        $this->get('/profil/tanda-tangan')->assertRedirect();
        $this->postJson('/profil/tanda-tangan', ['TandaTangan' => $this->gambarTandaTangan()])->assertUnauthorized();
        $this->assertSame(0, Pengguna::query()->whereNotNull('TandaTanganBerkasId')->count());
    }

    private function gambarTandaTangan(int $lebar = 480): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('tanda-tangan.png', GambarUji::pngTransparan($lebar, 140));
    }
}
