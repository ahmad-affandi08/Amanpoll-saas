<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\JenisFieldFormulir;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Domain\Enums\SumberProspek;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\FormulirPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;

/**
 * Otorisasi konsol form builder dan peta redirect (MARKETING.md 9, 10, 26).
 */
final class KonsolFormulirDanRedirectTest extends KasusHalaman
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::CMS);
    }

    public function test_admin_tanpa_izin_tidak_dapat_membuka_daftar_formulir(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.formulir.index'))
            ->assertForbidden();
    }

    public function test_izin_kelola_membuat_formulir_beserta_fieldnya(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.formulir.store'), $this->muatanFormulir())
            ->assertRedirect();

        $formulir = FormulirPemasaran::query()->where('Kode', 'demo')->first();

        $this->assertNotNull($formulir);
        $this->assertCount(2, $formulir->field);
    }

    public function test_menyimpan_ulang_menulis_ulang_seluruh_field(): void
    {
        $admin = $this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]);
        $this->actingAs($admin, 'platform')
            ->post(route('pemasaran.formulir.store'), $this->muatanFormulir());

        $this->actingAs($admin, 'platform')
            ->put(route('pemasaran.formulir.update', 'demo'), [
                ...$this->muatanFormulir(),
                'Field' => [[
                    'Kode' => 'Email',
                    'Label' => 'Email',
                    'Jenis' => JenisFieldFormulir::Email->value,
                    'Wajib' => true,
                ]],
            ])
            ->assertRedirect();

        $formulir = FormulirPemasaran::query()->where('Kode', 'demo')->firstOrFail();

        $this->assertSame(['Email'], $formulir->field->pluck('Kode')->all());
    }

    public function test_kode_formulir_ganda_ditolak(): void
    {
        $admin = $this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]);
        $this->actingAs($admin, 'platform')->post(route('pemasaran.formulir.store'), $this->muatanFormulir());

        $this->actingAs($admin, 'platform')
            ->post(route('pemasaran.formulir.store'), $this->muatanFormulir())
            ->assertSessionHasErrors('Kode');
    }

    public function test_izin_kelola_tidak_cukup_untuk_membuat_redirect(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.redirect.store'), [
                'Dari' => '/lama',
                'Ke' => '/baru',
                'Kode' => KodeRedirect::Permanen->value,
            ])
            ->assertForbidden();
    }

    public function test_izin_terbitkan_membuat_redirect_yang_langsung_berlaku(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_TERBITKAN]), 'platform')
            ->post(route('pemasaran.redirect.store'), [
                'Dari' => 'lama/',
                'Ke' => '/baru',
                'Kode' => KodeRedirect::Permanen->value,
            ])
            ->assertRedirect();

        $this->assertTrue(RedirectPemasaran::query()->where('Dari', '/lama')->exists());

        // Berlaku seketika: cache petanya dibuang saat aturannya disimpan.
        $this->get($this->urlPublik('/lama'))->assertStatus(301);
    }

    public function test_redirect_301_tanpa_tujuan_ditolak(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_TERBITKAN]), 'platform')
            ->post(route('pemasaran.redirect.store'), [
                'Dari' => '/lama',
                'Kode' => KodeRedirect::Permanen->value,
            ])
            ->assertSessionHasErrors('Ke');
    }

    public function test_tujuan_dibuang_pada_redirect_410(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_TERBITKAN]), 'platform')
            ->post(route('pemasaran.redirect.store'), [
                'Dari' => '/lama',
                'Ke' => '/baru',
                'Kode' => KodeRedirect::Hilang->value,
            ])
            ->assertRedirect();

        $this->assertNull(RedirectPemasaran::query()->where('Dari', '/lama')->firstOrFail()->Ke);
    }

    public function test_menghapus_redirect_mengembalikan_alamatnya(): void
    {
        $redirect = RedirectPemasaran::create([
            'Dari' => '/lama',
            'Ke' => '/baru',
            'Kode' => KodeRedirect::Permanen,
            'Aktif' => true,
        ]);
        app(PencariRedirectPemasaran::class)->buangCache();

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_TERBITKAN]), 'platform')
            ->delete(route('pemasaran.redirect.destroy', $redirect->Id))
            ->assertRedirect();

        $this->get($this->urlPublik('/lama'))->assertNotFound();
    }

    /** @return array<string, mixed> */
    private function muatanFormulir(): array
    {
        return [
            'Kode' => 'demo',
            'Nama' => 'Minta Demo',
            'Sumber' => SumberProspek::Demo->value,
            'Field' => [
                [
                    'Kode' => 'Nama',
                    'Label' => 'Nama',
                    'Jenis' => JenisFieldFormulir::Teks->value,
                    'Wajib' => true,
                ],
                [
                    'Kode' => 'Email',
                    'Label' => 'Email',
                    'Jenis' => JenisFieldFormulir::Email->value,
                    'Wajib' => true,
                ],
            ],
        ];
    }
}
