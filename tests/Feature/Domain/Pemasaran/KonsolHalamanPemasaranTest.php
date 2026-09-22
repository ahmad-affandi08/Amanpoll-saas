<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\HalamanPemasaran;

/**
 * Otorisasi dan alur konsol landing page builder (MARKETING.md 26, Gate 32).
 */
final class KonsolHalamanPemasaranTest extends KasusHalaman
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::CMS);
    }

    public function test_konsol_halaman_tertutup_saat_fitur_cms_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::CMS);

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_LIHAT]), 'platform')
            ->get(route('pemasaran.halaman.index'))
            ->assertNotFound();
    }

    public function test_halaman_terbit_tetap_dilayani_meski_fitur_cms_mati(): void
    {
        $this->buatTerbit('/harga');
        $this->matikanFitur(KatalogFiturPlatform::CMS);

        $this->get($this->urlPublik('/harga'))->assertOk();
    }

    public function test_admin_tanpa_izin_tidak_dapat_membuka_daftar_halaman(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.halaman.index'))
            ->assertForbidden();
    }

    public function test_izin_lihat_tidak_cukup_untuk_menyimpan_draf(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_LIHAT]), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatan())
            ->assertForbidden();
    }

    public function test_izin_kelola_tidak_cukup_untuk_menerbitkan(): void
    {
        $halaman = $this->buatDraf('/harga');

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.halaman.terbitkan', $halaman->Id))
            ->assertForbidden();
    }

    public function test_izin_kelola_cukup_untuk_menyimpan_draf(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatan())
            ->assertRedirect();

        $this->assertTrue(HalamanPemasaran::query()->where('Slug', '/harga')->exists());
    }

    public function test_izin_terbitkan_menerbitkan_halaman(): void
    {
        $halaman = $this->buatDraf('/harga');

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_TERBITKAN]), 'platform')
            ->post(route('pemasaran.halaman.terbitkan', $halaman->Id))
            ->assertRedirect();

        $this->assertSame(StatusHalamanPemasaran::Terbit, $halaman->fresh()?->Status);
        $this->get($this->urlPublik('/harga'))->assertOk();
    }

    public function test_slug_dinormalkan_sebelum_disimpan(): void
    {
        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.halaman.store'), [...$this->muatan(), 'Slug' => 'harga/'])
            ->assertRedirect();

        $this->assertTrue(HalamanPemasaran::query()->where('Slug', '/harga')->exists());
    }

    public function test_slug_ganda_ditolak(): void
    {
        $this->buatDraf('/harga');

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.halaman.store'), $this->muatan())
            ->assertSessionHasErrors('Slug');
    }

    public function test_blok_formulir_tanpa_formulir_ditolak(): void
    {
        $muatan = [...$this->muatan(), 'Blok' => [[
            'Jenis' => JenisBlokHalaman::Formulir->value,
            'Isi' => [],
        ]]];

        $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->post(route('pemasaran.halaman.store'), $muatan)
            ->assertStatus(422);
    }

    public function test_tautan_pratinjau_mengarah_ke_host_publik_dan_bertanda_tangan(): void
    {
        $halaman = $this->buatDraf('/harga');

        $respons = $this->actingAs($this->buatAdmin([KatalogIzinPemasaran::HALAMAN_KELOLA]), 'platform')
            ->get(route('pemasaran.halaman.pratinjau', [$halaman->Id, $halaman->VersiDrafId]));

        $tujuan = (string) $respons->headers->get('Location');

        $respons->assertRedirect();
        $this->assertStringContainsString((string) $this->host->publikKanonik(), $tujuan);
        $this->assertStringContainsString('signature=', $tujuan);
    }

    /** @return array<string, mixed> */
    private function muatan(): array
    {
        return [
            'Slug' => '/harga',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Harga Amanpoll',
            'Blok' => [['Jenis' => JenisBlokHalaman::Hero->value, 'Isi' => ['judul' => 'Harga']]],
        ];
    }
}
