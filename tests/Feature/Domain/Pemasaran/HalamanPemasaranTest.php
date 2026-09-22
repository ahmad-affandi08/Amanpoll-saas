<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use App\Domain\Pemasaran\Application\Actions\KembalikanVersiHalaman;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafHalaman;
use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Application\Actions\UbahStatusHalaman;
use App\Domain\Pemasaran\Domain\Enums\JenisBlokHalaman;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiHalamanPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/**
 * Landing page dibuat, diterbitkan, dan dikembalikan tanpa deploy (Gate 32).
 */
final class HalamanPemasaranTest extends KasusHalaman
{
    public function test_draf_tidak_terlihat_di_situs_publik(): void
    {
        $this->buatDraf('/harga');

        $this->get($this->urlPublik('/harga'))->assertNotFound();
    }

    public function test_halaman_terbit_dilayani_host_publik(): void
    {
        $this->buatTerbit('/harga', 'Harga Amanpoll');

        $respons = $this->get($this->urlPublik('/harga'));

        $respons->assertOk();
        $this->assertSame('Publik/Halaman', $respons->viewData('page')['component']);
        $this->assertSame('Harga Amanpoll', $respons->viewData('page')['props']['halaman']['Judul']);
    }

    public function test_blok_ikut_terkirim_ke_halaman_publik(): void
    {
        $this->buatTerbit('/fitur', 'Fitur', [
            ['Jenis' => JenisBlokHalaman::Hero->value, 'Isi' => ['judul' => 'Satu sistem']],
            ['Jenis' => JenisBlokHalaman::Faq->value, 'Isi' => ['judul' => 'Tanya jawab']],
        ]);

        $blok = $this->get($this->urlPublik('/fitur'))->viewData('page')['props']['halaman']['Blok'];

        $this->assertCount(2, $blok);
        $this->assertSame(JenisBlokHalaman::Hero->value, $blok[0]['Jenis']);
        $this->assertSame(JenisBlokHalaman::Faq->value, $blok[1]['Jenis']);
    }

    public function test_akar_situs_memakai_halaman_terbit_bila_ada(): void
    {
        $this->buatTerbit('/', 'Beranda Baru');

        $respons = $this->get($this->urlPublik('/'));

        $respons->assertOk();
        $this->assertSame('Publik/Halaman', $respons->viewData('page')['component']);
    }

    public function test_akar_situs_kembali_ke_beranda_bawaan_tanpa_halaman_terbit(): void
    {
        $respons = $this->get($this->urlPublik('/'));

        $respons->assertOk();
        $this->assertSame('Publik/Beranda', $respons->viewData('page')['component']);
    }

    public function test_setiap_simpan_melahirkan_versi_baru(): void
    {
        $halaman = $this->buatDraf('/harga', 'Judul Pertama');

        app(SimpanDrafHalaman::class)->jalankan($halaman, [
            'Slug' => '/harga',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Judul Kedua',
            'Blok' => [],
        ]);

        $nomor = VersiHalamanPemasaran::query()
            ->where('HalamanPemasaranId', $halaman->Id)
            ->orderBy('Nomor')
            ->pluck('Nomor')
            ->all();

        $this->assertSame([1, 2], $nomor);
    }

    public function test_menyunting_halaman_terbit_tidak_mengubah_yang_dilihat_publik(): void
    {
        $halaman = $this->buatTerbit('/harga', 'Judul Terbit');

        app(SimpanDrafHalaman::class)->jalankan($halaman, [
            'Slug' => '/harga',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Judul Draf',
            'Blok' => [],
        ]);

        $props = $this->get($this->urlPublik('/harga'))->viewData('page')['props'];

        $this->assertSame('Judul Terbit', $props['halaman']['Judul']);
    }

    public function test_rollback_menerbitkan_ulang_isi_versi_lama(): void
    {
        $halaman = $this->buatTerbit('/harga', 'Versi Satu');
        $versiSatu = $halaman->versiTerbit;
        $this->assertNotNull($versiSatu);

        app(SimpanDrafHalaman::class)->jalankan($halaman, [
            'Slug' => '/harga',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Versi Dua',
            'Blok' => [],
        ]);
        app(TerbitkanHalaman::class)->jalankan($halaman->fresh());

        $this->assertSame('Versi Dua', $this->judulTerbit('/harga'));

        app(KembalikanVersiHalaman::class)->jalankan($halaman->fresh(), $versiSatu);

        $this->assertSame('Versi Satu', $this->judulTerbit('/harga'));
    }

    public function test_rollback_membuat_versi_baru_bukan_menunjuk_mundur(): void
    {
        $halaman = $this->buatTerbit('/harga', 'Versi Satu');
        $versiSatu = $halaman->versiTerbit;
        $this->assertNotNull($versiSatu);

        app(SimpanDrafHalaman::class)->jalankan($halaman, [
            'Slug' => '/harga',
            'Tipe' => TipeHalamanPemasaran::Pricing->value,
            'Judul' => 'Versi Dua',
            'Blok' => [],
        ]);
        app(TerbitkanHalaman::class)->jalankan($halaman->fresh());
        app(KembalikanVersiHalaman::class)->jalankan($halaman->fresh(), $versiSatu);

        $this->assertSame(3, (int) VersiHalamanPemasaran::query()
            ->where('HalamanPemasaranId', $halaman->Id)
            ->max('Nomor'));
        $this->assertNotSame($versiSatu->Id, $halaman->fresh()?->VersiTerbitId);
    }

    public function test_penerbitan_tercatat_di_audit(): void
    {
        $halaman = $this->buatTerbit('/harga');

        $this->assertTrue(CatatanAudit::query()
            ->withoutGlobalScopes()
            ->where('Aksi', 'HalamanPemasaran.Diterbitkan')
            ->where('EntitasId', $halaman->Id)
            ->exists());
    }

    public function test_halaman_diarsipkan_hilang_dari_situs_publik(): void
    {
        $halaman = $this->buatTerbit('/harga');

        app(UbahStatusHalaman::class)->jalankan($halaman, StatusHalamanPemasaran::Diarsipkan);

        $this->get($this->urlPublik('/harga'))->assertNotFound();
    }

    public function test_halaman_terjadwal_terbit_saat_jadwalnya_lewat(): void
    {
        $halaman = $this->buatDraf('/harga');

        app(UbahStatusHalaman::class)->jalankan(
            $halaman,
            StatusHalamanPemasaran::Terjadwal,
            CarbonImmutable::now()->addHour(),
        );

        $this->get($this->urlPublik('/harga'))->assertNotFound();

        $this->travelTo(CarbonImmutable::now()->addHours(2));
        $this->artisan('pemasaran:jalankan-jadwal-halaman')->assertSuccessful();

        $this->get($this->urlPublik('/harga'))->assertOk();
    }

    public function test_halaman_tertarik_saat_jadwal_tariknya_lewat(): void
    {
        $halaman = $this->buatDraf('/harga');

        app(UbahStatusHalaman::class)->jalankan(
            $halaman,
            StatusHalamanPemasaran::Terjadwal,
            CarbonImmutable::now()->addHour(),
            CarbonImmutable::now()->addHours(3),
        );

        $this->travelTo(CarbonImmutable::now()->addHours(2));
        $this->artisan('pemasaran:jalankan-jadwal-halaman')->assertSuccessful();
        $this->get($this->urlPublik('/harga'))->assertOk();

        $this->travelTo(CarbonImmutable::now()->addHours(2));
        $this->artisan('pemasaran:jalankan-jadwal-halaman')->assertSuccessful();
        $this->get($this->urlPublik('/harga'))->assertNotFound();
    }

    public function test_jadwal_tanpa_waktu_terbit_ditolak(): void
    {
        $halaman = $this->buatDraf('/harga');

        $this->expectException(AturanBisnisDilanggar::class);

        app(UbahStatusHalaman::class)->jalankan($halaman, StatusHalamanPemasaran::Terjadwal);
    }

    public function test_halaman_diarsipkan_tidak_dapat_langsung_terbit(): void
    {
        $halaman = $this->buatDraf('/harga');
        app(UbahStatusHalaman::class)->jalankan($halaman, StatusHalamanPemasaran::Diarsipkan);

        $this->expectException(AturanBisnisDilanggar::class);

        app(TerbitkanHalaman::class)->jalankan($halaman->fresh());
    }

    public function test_versi_tidak_dapat_diubah_setelah_dibuat(): void
    {
        $halaman = $this->buatDraf('/harga');
        $versi = $halaman->versiDraf;
        $this->assertNotNull($versi);

        $this->expectException(AturanBisnisDilanggar::class);

        $versi->Judul = 'Ditimpa';
        $versi->save();
    }

    public function test_sitemap_memuat_halaman_terbit_saja(): void
    {
        $this->buatTerbit('/harga');
        $this->buatDraf('/draf');

        $isi = $this->get($this->urlPublik('/sitemap.xml'))->getContent() ?: '';

        $this->assertStringContainsString('/harga</loc>', $isi);
        $this->assertStringNotContainsString('/draf</loc>', $isi);
    }

    public function test_halaman_noindex_tidak_masuk_sitemap(): void
    {
        $halaman = $this->buatDraf('/rahasia', 'Rahasia', null, ['NoIndex' => true]);
        app(TerbitkanHalaman::class)->jalankan($halaman);

        $isi = $this->get($this->urlPublik('/sitemap.xml'))->getContent() ?: '';

        $this->assertStringNotContainsString('/rahasia</loc>', $isi);
    }

    private function judulTerbit(string $jalur): string
    {
        return (string) $this->get($this->urlPublik($jalur))
            ->viewData('page')['props']['halaman']['Judul'];
    }
}
