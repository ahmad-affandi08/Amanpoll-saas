<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Domain\Enums\JenisKontenPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusHalamanPemasaran;
use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KeywordSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenKeywordSeo;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontenPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;

/** Konsol CMS konten dan keyword manager beserta penjaganya (MARKETING.md 9). */
final class KonsolKontenTest extends KasusKonten
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->nyalakanFitur(KatalogFiturPlatform::CMS);
    }

    public function test_konsol_tertutup_saat_flag_cms_mati(): void
    {
        $this->matikanFitur(KatalogFiturPlatform::CMS);

        $this->actingAs($this->pengelola(), 'platform')
            ->get(route('pemasaran.konten.index'))
            ->assertNotFound();
    }

    public function test_tanpa_izin_konten_konsol_ditolak(): void
    {
        $this->actingAs($this->buatAdmin(), 'platform')
            ->get(route('pemasaran.konten.index'))
            ->assertForbidden();
    }

    public function test_konten_baru_lahir_sebagai_draf(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.store'), $this->muatan())
            ->assertRedirect();

        $konten = KontenPemasaran::query()->firstOrFail();

        $this->assertSame(StatusHalamanPemasaran::Draf, $konten->Status);
        $this->assertSame('/artikel/panduan-cmms', $konten->Slug);
        $this->assertNotNull($konten->VersiDrafId);
        $this->assertNull($konten->VersiTerbitId);
    }

    /** Menerbitkan mengubah isi situs publik, jadi izinnya berbeda dari mengelola. */
    public function test_pengelola_tanpa_izin_terbitkan_tidak_dapat_menerbitkan(): void
    {
        $konten = $this->buatDraf();

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.terbitkan', $konten->Id))
            ->assertForbidden();

        $this->assertNull($konten->fresh()->VersiTerbitId);
    }

    public function test_penerbit_dapat_menerbitkan(): void
    {
        $konten = $this->buatDraf();

        $this->actingAs($this->penerbit(), 'platform')
            ->post(route('pemasaran.konten.terbitkan', $konten->Id))
            ->assertRedirect();

        $this->assertNotNull($konten->fresh()->VersiTerbitId);
    }

    public function test_slug_berhuruf_besar_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Slug'] = 'Panduan CMMS';

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.store'), $muatan)
            ->assertSessionHasErrors('Slug');
    }

    public function test_jenis_di_luar_daftar_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['Jenis'] = 'Podcast';

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.store'), $muatan)
            ->assertSessionHasErrors('Jenis');
    }

    /** Penjaga ramah di formulir; penjaga domain tetap berdiri sendiri di aksinya. */
    public function test_jalur_yang_sudah_dipakai_ditolak_di_formulir(): void
    {
        $this->buatDraf('panduan-cmms');

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.store'), $this->muatan())
            ->assertSessionHasErrors('Slug');

        $this->assertSame(1, KontenPemasaran::query()->count());
    }

    public function test_naskah_kosong_ditolak(): void
    {
        $muatan = $this->muatan();
        $muatan['IsiMarkdown'] = '';

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.store'), $muatan)
            ->assertSessionHasErrors('IsiMarkdown');
    }

    public function test_menyimpan_membuat_versi_baru_bukan_menimpa(): void
    {
        $konten = $this->buatTerbit('panduan-cmms', 'Judul Lama');

        $muatan = $this->muatan();
        $muatan['Judul'] = 'Judul Baru';

        $this->actingAs($this->pengelola(), 'platform')
            ->put(route('pemasaran.konten.update', $konten->Id), $muatan)
            ->assertRedirect();

        $konten = $konten->fresh();

        $this->assertSame(2, $konten->versi()->count());
        $this->assertNotSame($konten->VersiTerbitId, $konten->VersiDrafId);
        $this->assertSame('Judul Lama', $konten->versiTerbit?->Judul);
    }

    public function test_keyword_baru_tersimpan_dengan_intentnya(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.keyword.store'), [
                'Keyword' => 'software cmms',
                'Intent' => 'COMMERCIAL',
                'Prioritas' => 'Tinggi',
                'Status' => 'Ditargetkan',
            ])
            ->assertRedirect();

        $keyword = KeywordSeo::query()->firstOrFail();

        $this->assertSame('COMMERCIAL', $keyword->Intent->value);
        $this->assertSame('Membandingkan pilihan', $keyword->Intent->label());
    }

    public function test_intent_di_luar_daftar_ditolak(): void
    {
        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.keyword.store'), [
                'Keyword' => 'software cmms',
                'Intent' => 'Penasaran',
                'Prioritas' => 'Tinggi',
                'Status' => 'Ide',
            ])
            ->assertSessionHasErrors('Intent');
    }

    public function test_keyword_kembar_ditolak(): void
    {
        $this->buatKeyword('software cmms');

        $this->actingAs($this->pengelola(), 'platform')
            ->post(route('pemasaran.konten.keyword.store'), [
                'Keyword' => 'software cmms',
                'Intent' => 'COMMERCIAL',
                'Prioritas' => 'Tinggi',
                'Status' => 'Ide',
            ])
            ->assertSessionHasErrors('Keyword');
    }

    /** Satu konten hanya punya satu keyword utama; yang baru menurunkan yang lama. */
    public function test_keyword_utama_hanya_satu(): void
    {
        $konten = $this->buatDraf();
        $pertama = $this->buatKeyword('software cmms');
        $kedua = $this->buatKeyword('aplikasi maintenance');

        $admin = $this->pengelola();

        $this->actingAs($admin, 'platform')->post(
            route('pemasaran.konten.keyword.tautkan', $konten->Id),
            ['KeywordSeoId' => $pertama->Id, 'Utama' => true],
        )->assertRedirect();

        $this->actingAs($admin, 'platform')->post(
            route('pemasaran.konten.keyword.tautkan', $konten->Id),
            ['KeywordSeoId' => $kedua->Id, 'Utama' => true],
        )->assertRedirect();

        $utama = KontenKeywordSeo::query()
            ->where('KontenPemasaranId', $konten->Id)
            ->where('Utama', true)
            ->get();

        $this->assertCount(1, $utama);
        $this->assertSame($kedua->Id, $utama->first()?->KeywordSeoId);
    }

    public function test_menautkan_keyword_yang_sama_dua_kali_tidak_menggandakan(): void
    {
        $konten = $this->buatDraf();
        $keyword = $this->buatKeyword('software cmms');
        $admin = $this->pengelola();

        foreach (range(1, 3) as $ke) {
            $this->actingAs($admin, 'platform')->post(
                route('pemasaran.konten.keyword.tautkan', $konten->Id),
                ['KeywordSeoId' => $keyword->Id],
            );
        }

        $this->assertSame(1, KontenKeywordSeo::query()->count());
    }

    public function test_keyword_dapat_dilepas(): void
    {
        $konten = $this->buatDraf();
        $keyword = $this->buatKeyword('software cmms');
        $admin = $this->pengelola();

        $this->actingAs($admin, 'platform')->post(
            route('pemasaran.konten.keyword.tautkan', $konten->Id),
            ['KeywordSeoId' => $keyword->Id],
        );

        $this->actingAs($admin, 'platform')
            ->delete(route('pemasaran.konten.keyword.lepas', [$konten->Id, $keyword->Id]))
            ->assertRedirect();

        $this->assertSame(0, KontenKeywordSeo::query()->count());
    }

    private function buatKeyword(string $keyword): KeywordSeo
    {
        return KeywordSeo::create([
            'Keyword' => $keyword,
            'Intent' => 'COMMERCIAL',
            'Prioritas' => 'Sedang',
            'Status' => 'Ide',
        ]);
    }

    /** @return array<string, mixed> */
    private function muatan(): array
    {
        return [
            'Slug' => 'panduan-cmms',
            'Jenis' => JenisKontenPemasaran::Artikel->value,
            'Judul' => 'Panduan CMMS',
            'IsiMarkdown' => "## Pembuka\n\nIsi naskah.",
        ];
    }

    private function pengelola(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::KONTEN_LIHAT,
            KatalogIzinPemasaran::KONTEN_KELOLA,
        ]);
    }

    private function penerbit(): AdminPlatform
    {
        return $this->buatAdmin([
            KatalogIzinPemasaran::KONTEN_LIHAT,
            KatalogIzinPemasaran::KONTEN_KELOLA,
            KatalogIzinPemasaran::KONTEN_TERBITKAN,
        ]);
    }
}
