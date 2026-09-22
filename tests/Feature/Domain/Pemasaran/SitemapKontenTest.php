<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\SimpanDrafHalaman;
use App\Domain\Pemasaran\Application\Actions\SimpanDrafKonten;
use App\Domain\Pemasaran\Application\Actions\TerbitkanHalaman;
use App\Domain\Pemasaran\Application\Actions\TerbitkanKonten;
use App\Domain\Pemasaran\Application\Actions\UbahStatusKonten;
use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\Enums\TipeHalamanPemasaran;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiKontenPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Artikel terbit muncul di sitemap dengan metadata lengkap; yang noindex tidak pernah (Gate 38.04). */
final class SitemapKontenTest extends KasusKonten
{
    /** Inti Gate 38.04. */
    public function test_konten_terbit_masuk_sitemap(): void
    {
        $this->buatTerbit('panduan-cmms');

        $this->assertStringContainsString('/artikel/panduan-cmms</loc>', $this->isiSitemap());
    }

    public function test_konten_draf_tidak_masuk_sitemap(): void
    {
        $this->buatDraf('belum-jadi');

        $this->assertStringNotContainsString('/artikel/belum-jadi</loc>', $this->isiSitemap());
    }

    /** Sisi lain Gate 38.04: noindex tidak pernah muncul, walau statusnya terbit. */
    public function test_konten_noindex_tidak_pernah_masuk_sitemap(): void
    {
        $konten = $this->buatTerbit('rahasia', 'Rahasia', tambahan: ['NoIndex' => true]);

        $this->assertTrue($konten->Status->terlihatPublik());
        $this->assertNotNull($konten->VersiTerbitId);
        $this->assertStringNotContainsString('/artikel/rahasia</loc>', $this->isiSitemap());
    }

    /** Status terbit saja tidak cukup; tanpa versi terkunci tidak ada yang bisa ditayangkan. */
    public function test_konten_terbit_tanpa_versi_terkunci_tidak_masuk_sitemap(): void
    {
        $konten = $this->buatTerbit('tanpa-versi');
        $konten->VersiTerbitId = null;
        $konten->save();

        $this->assertStringNotContainsString('/artikel/tanpa-versi</loc>', $this->isiSitemap());
    }

    /** Responsnya disimpan sejam, jadi peta situs hanya diminta sekali di sini. */
    public function test_konten_diarsipkan_keluar_dari_sitemap(): void
    {
        $konten = $this->buatTerbit('pernah-terbit');
        $this->assertTrue($konten->bolehMasukSitemap());

        app(UbahStatusKonten::class)->jalankan($konten, StatusHalamanPemasaran::Diarsipkan);

        $this->assertFalse($konten->fresh()->bolehMasukSitemap());
        $this->assertStringNotContainsString('/artikel/pernah-terbit</loc>', $this->isiSitemap());
    }

    /** Sitemap tidak boleh kehilangan halaman pemasaran ketika konten ikut masuk. */
    public function test_sitemap_memuat_halaman_dan_konten_sekaligus(): void
    {
        $this->buatTerbit('panduan-cmms');
        app(TerbitkanHalaman::class)->jalankan(
            app(SimpanDrafHalaman::class)->jalankan(null, [
                'Slug' => '/harga',
                'Tipe' => TipeHalamanPemasaran::Pricing->value,
                'Judul' => 'Harga',
                'Blok' => [],
            ]),
        );

        $isi = $this->isiSitemap();

        $this->assertStringContainsString('/harga</loc>', $isi);
        $this->assertStringContainsString('/artikel/panduan-cmms</loc>', $isi);
    }

    public function test_setiap_jenis_konten_punya_raknya_sendiri(): void
    {
        $this->buatTerbit('template-audit-aset', 'Template Audit Aset', JenisKontenPemasaran::FreeTool);
        $this->buatTerbit('pabrik-x', 'Pabrik X', JenisKontenPemasaran::CaseStudy);

        $isi = $this->isiSitemap();

        $this->assertStringContainsString('/tools/template-audit-aset</loc>', $isi);
        $this->assertStringContainsString('/studi-kasus/pabrik-x</loc>', $isi);
    }

    /** Halaman publiknya harus benar-benar dapat dibuka, bukan sekadar terdaftar di sitemap. */
    public function test_konten_terbit_tampil_dengan_metadata_lengkap(): void
    {
        $this->buatTerbit('panduan-cmms', 'Panduan CMMS', tambahan: [
            'Ringkasan' => 'Ringkasan panduan.',
            'MetaJudul' => 'Panduan CMMS untuk Pabrik',
            'MetaDeskripsi' => 'Cara memilih CMMS.',
            'OgGambar' => 'https://contoh.test/og.png',
            'SkemaTipe' => 'Article',
        ]);

        $props = $this->get($this->urlPublik('/artikel/panduan-cmms'))
            ->assertOk()
            ->viewData('page')['props']['konten'];

        $this->assertSame('Panduan CMMS untuk Pabrik', $props['Meta']['Judul']);
        $this->assertSame('Cara memilih CMMS.', $props['Meta']['Deskripsi']);
        $this->assertSame('https://contoh.test/og.png', $props['Meta']['OgGambar']);
        $this->assertSame('Article', $props['Meta']['SkemaTipe']);
        $this->assertFalse($props['NoIndex']);
    }

    public function test_konten_draf_tidak_dapat_dibuka_publik(): void
    {
        $this->buatDraf('belum-jadi');

        $this->get($this->urlPublik('/artikel/belum-jadi'))->assertNotFound();
    }

    /**
     * Slug, penulis, dan noindex melekat pada kontennya, bukan pada versinya,
     * jadi menyimpan draf langsung mengubah yang sedang tayang.
     */
    public function test_menyimpan_draf_memperbarui_data_konten_yang_sedang_tayang(): void
    {
        $konten = $this->buatTerbit('panduan-cmms', 'Judul Lama');
        $this->get($this->urlPublik('/artikel/panduan-cmms'))->assertOk();

        app(SimpanDrafKonten::class)->jalankan($konten, [
            'Slug' => 'panduan-cmms',
            'Jenis' => JenisKontenPemasaran::Artikel->value,
            'Judul' => 'Judul Baru',
            'IsiMarkdown' => 'Naskah baru.',
            'PenulisNama' => 'Sri Rahayu',
        ]);

        $props = $this->get($this->urlPublik('/artikel/panduan-cmms'))
            ->assertOk()
            ->viewData('page')['props']['konten'];

        $this->assertSame('Sri Rahayu', $props['PenulisNama']);
        $this->assertSame('Judul Lama', $props['Judul']);
    }

    /** Yang ditarik harus hilang seketika, termasuk dari isi yang sudah tersimpan. */
    public function test_konten_diarsipkan_tidak_lagi_dapat_dibuka(): void
    {
        $konten = $this->buatTerbit('panduan-cmms');
        $this->get($this->urlPublik('/artikel/panduan-cmms'))->assertOk();

        app(UbahStatusKonten::class)->jalankan($konten, StatusHalamanPemasaran::Diarsipkan);

        $this->get($this->urlPublik('/artikel/panduan-cmms'))->assertNotFound();
    }

    /** Yang tayang adalah versi terkunci, bukan draf yang masih disunting. */
    public function test_draf_baru_tidak_mengubah_yang_sedang_tayang(): void
    {
        $konten = $this->buatTerbit('panduan-cmms', 'Judul Lama');

        app(SimpanDrafKonten::class)->jalankan($konten, [
            'Slug' => 'panduan-cmms',
            'Jenis' => JenisKontenPemasaran::Artikel->value,
            'Judul' => 'Judul Baru',
            'IsiMarkdown' => 'Naskah baru.',
        ]);

        $props = $this->get($this->urlPublik('/artikel/panduan-cmms'))
            ->assertOk()
            ->viewData('page')['props']['konten'];

        $this->assertSame('Judul Lama', $props['Judul']);
        $this->assertSame(2, VersiKontenPemasaran::query()->count());
    }

    /** Isi terbit disimpan sementara, jadi penerbitan ulang harus ikut membuangnya. */
    public function test_penerbitan_ulang_memindahkan_versi_yang_tayang(): void
    {
        $konten = $this->buatTerbit('panduan-cmms', 'Judul Lama');
        $this->get($this->urlPublik('/artikel/panduan-cmms'))->assertOk();

        $konten = app(SimpanDrafKonten::class)->jalankan($konten, [
            'Slug' => 'panduan-cmms',
            'Jenis' => JenisKontenPemasaran::Artikel->value,
            'Judul' => 'Judul Baru',
            'IsiMarkdown' => 'Naskah baru.',
        ]);
        app(TerbitkanKonten::class)->jalankan($konten);

        $props = $this->get($this->urlPublik('/artikel/panduan-cmms'))
            ->assertOk()
            ->viewData('page')['props']['konten'];

        $this->assertSame('Judul Baru', $props['Judul']);
        $this->assertSame(2, $props['VersiNomor']);
    }

    public function test_konten_tanpa_versi_tidak_dapat_diterbitkan(): void
    {
        $konten = $this->buatDraf('panduan-cmms');
        $konten->VersiDrafId = null;
        $konten->save();

        $this->expectException(AturanBisnisDilanggar::class);

        app(TerbitkanKonten::class)->jalankan($konten->fresh());
    }

    public function test_versi_milik_konten_lain_tidak_dapat_diterbitkan(): void
    {
        $pertama = $this->buatDraf('satu');
        $kedua = $this->buatDraf('dua');

        $this->expectException(AturanBisnisDilanggar::class);

        app(TerbitkanKonten::class)->jalankan($pertama, $kedua->versiDraf);
    }

    /** Konten yang diarsipkan tidak boleh melompat langsung kembali ke terbit. */
    public function test_konten_diarsipkan_tidak_dapat_langsung_diterbitkan(): void
    {
        $konten = $this->buatTerbit('panduan-cmms');
        app(UbahStatusKonten::class)->jalankan($konten, StatusHalamanPemasaran::Diarsipkan);

        $this->expectException(AturanBisnisDilanggar::class);

        app(TerbitkanKonten::class)->jalankan($konten->fresh());
    }

    public function test_penerbitan_tidak_boleh_lewat_ubah_status(): void
    {
        $konten = $this->buatDraf('panduan-cmms');

        $this->expectException(AturanBisnisDilanggar::class);

        app(UbahStatusKonten::class)->jalankan($konten, StatusHalamanPemasaran::Terbit);
    }

    public function test_artikel_dilihat_tercatat_saat_konten_dibuka(): void
    {
        $this->buatTerbit('panduan-cmms');

        $this->get($this->urlPublik('/artikel/panduan-cmms'))->assertOk();

        $event = EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::ARTIKEL_DILIHAT)
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('/artikel/panduan-cmms', $event->DataTambahan['Slug'] ?? null);
        $this->assertSame('Artikel', $event->DataTambahan['Jenis'] ?? null);
    }

    public function test_artikel_dilihat_tidak_tercatat_untuk_konten_yang_tidak_ada(): void
    {
        $this->get($this->urlPublik('/artikel/entah-apa'))->assertNotFound();

        $this->assertSame(0, EventPemasaran::query()
            ->where('Jenis', KatalogPeristiwaPemasaran::ARTIKEL_DILIHAT)->count());
    }

    /** Dua konten tidak boleh berbagi satu alamat publik. */
    public function test_jalur_yang_sama_ditolak(): void
    {
        $this->buatDraf('panduan-cmms');

        $this->expectException(AturanBisnisDilanggar::class);

        $this->buatDraf('panduan-cmms', 'Panduan Lain');
    }

    /** Rute konten dikenali lebih dulu, jadi jalur kembar akan menyembunyikan halamannya. */
    public function test_jalur_yang_sudah_dipakai_halaman_ditolak(): void
    {
        app(SimpanDrafHalaman::class)->jalankan(null, [
            'Slug' => '/artikel/panduan-cmms',
            'Tipe' => TipeHalamanPemasaran::General->value,
            'Judul' => 'Halaman Lama',
            'Blok' => [],
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        $this->buatDraf('panduan-cmms');
    }

    public function test_slug_lahir_dari_jenisnya(): void
    {
        $konten = $this->buatDraf('template-audit-aset', 'Template Audit', JenisKontenPemasaran::FreeTool);

        $this->assertSame('/tools/template-audit-aset', $konten->Slug);
        $this->assertSame(
            '/tools/template-audit-aset',
            KontenPemasaran::query()->whereKey($konten->Id)->value('Slug'),
        );
    }
}
