<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\PencariRedirectPemasaran;
use App\Domain\Pemasaran\Domain\Enums\KodeRedirect;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\RedirectPemasaran;

/** Peta redirect situs publik (MARKETING.md 9, TASK 32.03). */
final class RedirectPemasaranTest extends KasusHalaman
{
    public function test_redirect_permanen_mengalihkan_dengan_301(): void
    {
        $this->buatRedirect('/harga-lama', '/harga', KodeRedirect::Permanen);

        $this->get($this->urlPublik('/harga-lama'))
            ->assertStatus(301)
            ->assertRedirect('/harga');
    }

    public function test_redirect_sementara_mengalihkan_dengan_302(): void
    {
        $this->buatRedirect('/promo', '/harga', KodeRedirect::Sementara);

        $this->get($this->urlPublik('/promo'))->assertStatus(302);
    }

    public function test_redirect_hilang_menjawab_410(): void
    {
        $this->buatRedirect('/produk-dihentikan', null, KodeRedirect::Hilang);

        $this->get($this->urlPublik('/produk-dihentikan'))->assertStatus(410);
    }

    public function test_redirect_nonaktif_diabaikan(): void
    {
        $redirect = $this->buatRedirect('/harga-lama', '/harga', KodeRedirect::Permanen);
        $redirect->update(['Aktif' => false]);
        app(PencariRedirectPemasaran::class)->buangCache();

        $this->get($this->urlPublik('/harga-lama'))->assertNotFound();
    }

    public function test_redirect_tidak_berlaku_di_host_dashboard(): void
    {
        $this->buatRedirect('/login', '/harga', KodeRedirect::Permanen);

        $this->get('http://'.$this->host->dashboard().'/login')
            ->assertOk();
    }

    public function test_redirect_mendahului_halaman_terbit_dengan_slug_sama(): void
    {
        $this->buatTerbit('/harga', 'Harga');
        $this->buatRedirect('/harga', '/harga-baru', KodeRedirect::Permanen);

        $this->get($this->urlPublik('/harga'))->assertStatus(301);
    }

    public function test_garis_miring_penutup_dianggap_alamat_yang_sama(): void
    {
        $this->buatRedirect('/harga-lama/', '/harga', KodeRedirect::Permanen);

        $this->get($this->urlPublik('/harga-lama'))->assertStatus(301);
    }

    public function test_pemakaian_redirect_tercatat(): void
    {
        $redirect = $this->buatRedirect('/harga-lama', '/harga', KodeRedirect::Permanen);
        $diperbaruiSemula = $redirect->DiperbaruiPada;

        $this->get($this->urlPublik('/harga-lama'));

        $segar = $redirect->fresh();
        $this->assertNotNull($segar);
        $this->assertSame(1, $segar->JumlahDipakai);
        $this->assertNotNull($segar->TerakhirDipakaiPada);
        // Kunjungan bukan penyuntingan: DiperbaruiPada tidak boleh ikut maju.
        $this->assertEquals($diperbaruiSemula, $segar->DiperbaruiPada);
    }

    private function buatRedirect(string $dari, ?string $ke, KodeRedirect $kode): RedirectPemasaran
    {
        $redirect = RedirectPemasaran::create([
            'Dari' => PencariRedirectPemasaran::normalkan($dari),
            'Ke' => $ke,
            'Kode' => $kode,
            'Aktif' => true,
        ]);

        app(PencariRedirectPemasaran::class)->buangCache();

        return $redirect;
    }
}
