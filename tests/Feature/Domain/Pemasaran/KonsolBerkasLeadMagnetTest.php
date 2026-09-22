<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\BerkasLeadMagnet;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Unggah dan cabut berkas lead magnet di konsol (MARKETING.md 10). */
final class KonsolBerkasLeadMagnetTest extends KasusLeadMagnet
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::CMS);
    }

    public function test_pengelola_dapat_mengunggah_berkas(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.formulir.berkas.simpan', $formulir->Kode), [
                'Berkas' => $this->berkasContoh(),
            ])
            ->assertRedirect();

        $segar = $formulir->fresh();

        $this->assertTrue($segar?->punyaBerkas());
        $this->assertSame('template-preventive.pdf', $segar?->BerkasNamaAsli);
        Storage::disk(BerkasLeadMagnet::disk())->assertExists((string) $segar?->BerkasLokasi);
    }

    public function test_tanpa_izin_kelola_unggahan_ditolak(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_LIHAT]), 'platform')
            ->post(route('pemasaran.formulir.berkas.simpan', $formulir->Kode), [
                'Berkas' => $this->berkasContoh(),
            ])
            ->assertForbidden();

        $this->assertFalse($formulir->fresh()?->punyaBerkas());
    }

    public function test_anonim_tidak_dapat_mengunggah(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        $this->post(route('pemasaran.formulir.berkas.simpan', $formulir->Kode), [
            'Berkas' => $this->berkasContoh(),
        ])->assertRedirect();

        $this->assertFalse($formulir->fresh()?->punyaBerkas());
    }

    /** Lead magnet adalah dokumen, bukan berkas yang dapat dieksekusi. */
    public function test_berkas_yang_dapat_dieksekusi_ditolak(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.formulir.berkas.simpan', $formulir->Kode), [
                'Berkas' => UploadedFile::fake()->create('jahat.php', 4, 'application/x-php'),
            ])
            ->assertSessionHasErrors('Berkas');

        $this->assertFalse($formulir->fresh()?->punyaBerkas());
    }

    public function test_berkas_terlalu_besar_ditolak(): void
    {
        $formulir = $this->buatFormulir(denganBerkas: false);

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.formulir.berkas.simpan', $formulir->Kode), [
                'Berkas' => UploadedFile::fake()->create('besar.pdf', 20481, 'application/pdf'),
            ])
            ->assertSessionHasErrors('Berkas');
    }

    public function test_pengelola_dapat_mencabut_berkas(): void
    {
        $formulir = $this->buatFormulir();
        $lokasi = (string) $formulir->BerkasLokasi;

        $this->actingAs($this->pengelola(), 'platform')
            ->delete(route('pemasaran.formulir.berkas.hapus', $formulir->Kode))
            ->assertRedirect();

        $this->assertFalse($formulir->fresh()?->punyaBerkas());
        Storage::disk(BerkasLeadMagnet::disk())->assertMissing($lokasi);
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::HALAMAN_LIHAT,
            KatalogIzinPemasaran::HALAMAN_KELOLA,
        ]);
    }
}
