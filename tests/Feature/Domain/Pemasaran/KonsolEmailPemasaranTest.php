<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Actions\CatatPermintaanData;
use App\Domain\Pemasaran\Application\Actions\DaftarkanKeSequence;
use App\Domain\Pemasaran\Application\Actions\ProsesPermintaanData;
use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\LayananSequenceEmail;
use App\Domain\Pemasaran\Application\Services\LayananTemplateEmail;
use App\Domain\Pemasaran\Domain\Enums\JenisPermintaanData;
use App\Domain\Pemasaran\Domain\Enums\JenisTemplateEmail;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\DaftarSupresi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KonsenPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\KontakProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PermintaanDataProspek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TemplateEmailPemasaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\AdminPlatform;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Konsol template, sequence, dan permintaan data (MARKETING.md 15, 27). */
final class KonsolEmailPemasaranTest extends KasusEmailPemasaran
{
    private const AKAR = '/admin-platform/pemasaran/email';

    public function test_template_dengan_variabel_salah_ketik_ditolak(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::EMAIL_KELOLA])
            ->post(self::AKAR.'/template', $this->isiTemplate(['Subjek' => 'Halo {{NamaDepan}}']))
            ->assertSessionHasErrors();

        $this->assertSame(0, TemplateEmailPemasaran::query()->count());
    }

    public function test_template_dengan_variabel_dikenal_tersimpan(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::EMAIL_KELOLA])
            ->post(self::AKAR.'/template', $this->isiTemplate())
            ->assertRedirect();

        $this->assertSame(1, TemplateEmailPemasaran::query()->where('Kode', 'sapaan')->count());
    }

    public function test_melihat_konsol_email_menuntut_izin(): void
    {
        $this->aktingSebagai([])->get(self::AKAR.'/template')->assertForbidden();
        $this->aktingSebagai([KatalogIzinPemasaran::EMAIL_LIHAT])->get(self::AKAR.'/template')->assertOk();
    }

    public function test_izin_melihat_saja_tidak_boleh_menyimpan_template(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::EMAIL_LIHAT])
            ->post(self::AKAR.'/template', $this->isiTemplate())
            ->assertForbidden();
    }

    public function test_langkah_tidak_dapat_diubah_selagi_ada_pendaftaran_berjalan(): void
    {
        $sequence = $this->buatSequence([0, 3]);
        app(DaftarkanKeSequence::class)->jalankan($sequence, $this->buatProspek());
        $langkah = $sequence->langkah()->firstOrFail();

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananSequenceEmail::class)
            ->hapusLangkah($sequence, $langkah);
    }

    public function test_template_yang_masih_dipakai_langkah_tidak_dapat_dihapus(): void
    {
        $sequence = $this->buatSequence([0]);
        $template = $sequence->langkah()->firstOrFail()->template;

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananTemplateEmail::class)
            ->hapus($template);
    }

    public function test_permintaan_data_langsung_menyupresi_alamatnya(): void
    {
        $prospek = $this->buatProspek();

        app(CatatPermintaanData::class)->jalankan(
            (string) $prospek->Email,
            JenisPermintaanData::Anonimisasi,
            $prospek,
        );

        $this->assertTrue(app(LayananKonsen::class)->disupresi((string) $prospek->Email));
    }

    public function test_anonimisasi_melepas_identitas_tetapi_menyisakan_barisnya(): void
    {
        $prospek = $this->buatProspek();
        KontakProspek::create([
            'ProspekId' => $prospek->Id,
            'Nama' => 'Budi',
            'Email' => 'budi@pabrik.test',
            'Utama' => true,
        ]);
        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $prospek);

        $permintaan = app(CatatPermintaanData::class)->jalankan(
            'budi@pabrik.test',
            JenisPermintaanData::Anonimisasi,
            $prospek,
        );
        app(ProsesPermintaanData::class)->jalankan($permintaan);

        $sesudah = $prospek->fresh();
        $this->assertNotNull($sesudah);
        $this->assertNull($sesudah->Email);
        $this->assertSame(ProsesPermintaanData::NAMA_ANONIM, $sesudah->Nama);
        $this->assertSame(0, KontakProspek::query()->where('ProspekId', $prospek->Id)->count());
        $this->assertSame('', (string) PengirimanEmailPemasaran::query()->firstOrFail()->Email);
    }

    public function test_penghapusan_membuang_prospek_dan_alamatnya_di_mana_pun(): void
    {
        $prospek = $this->buatProspek();

        $permintaan = app(CatatPermintaanData::class)->jalankan(
            'budi@pabrik.test',
            JenisPermintaanData::Penghapusan,
            $prospek,
        );
        app(ProsesPermintaanData::class)->jalankan($permintaan);

        $this->assertNull(Prospek::query()->find($prospek->Id));
        $this->assertSame(0, KonsenPemasaran::query()->where('Email', 'budi@pabrik.test')->count());
        $this->assertNull(PermintaanDataProspek::query()->findOrFail($permintaan->Id)->Email);
        $this->assertSame(0, DaftarSupresi::query()
            ->where('Email', 'budi@pabrik.test')->count());
    }

    /** Alamat yang datanya sudah dihapus tetap tidak boleh menerima surat lagi. */
    public function test_alamat_yang_dihapus_tetap_tertolak_walau_mendaftar_ulang(): void
    {
        $prospek = $this->buatProspek();

        $permintaan = app(CatatPermintaanData::class)->jalankan(
            'budi@pabrik.test',
            JenisPermintaanData::Penghapusan,
            $prospek,
        );
        app(ProsesPermintaanData::class)->jalankan($permintaan);

        $baru = $this->buatProspek();

        $this->assertFalse(app(LayananKonsen::class)->bolehDikirimi('budi@pabrik.test'));

        $this->expectException(AturanBisnisDilanggar::class);

        app(DaftarkanKeSequence::class)->jalankan($this->buatSequence([0]), $baru);
    }

    public function test_permintaan_yang_sudah_diproses_tidak_diproses_dua_kali(): void
    {
        $permintaan = app(CatatPermintaanData::class)->jalankan(
            'budi@pabrik.test',
            JenisPermintaanData::Anonimisasi,
        );

        app(ProsesPermintaanData::class)->jalankan($permintaan);

        $this->expectException(AturanBisnisDilanggar::class);

        app(ProsesPermintaanData::class)->jalankan($permintaan);
    }

    public function test_memproses_permintaan_menuntut_izin_ekspor(): void
    {
        $permintaan = app(CatatPermintaanData::class)->jalankan(
            'budi@pabrik.test',
            JenisPermintaanData::Anonimisasi,
        );

        $this->aktingSebagai([KatalogIzinPemasaran::EMAIL_KELOLA])
            ->post(self::AKAR."/konsen/permintaan/{$permintaan->Id}/proses")
            ->assertForbidden();

        $this->assertNull($permintaan->fresh()?->DiprosesPada);
    }

    /** @param list<string> $izin */
    private function aktingSebagai(array $izin): self
    {
        $admin = $this->buatAdmin($izin);
        $this->actingAs($admin, 'platform');

        $this->assertInstanceOf(AdminPlatform::class, $admin);

        return $this;
    }

    /** @param array<string, mixed> $ganti */
    private function isiTemplate(array $ganti = []): array
    {
        return array_merge([
            'Kode' => 'sapaan',
            'Nama' => 'Sapaan',
            'Jenis' => JenisTemplateEmail::Trial->value,
            'Subjek' => 'Halo {{Nama}}',
            'IsiHtml' => '<p>Sisa trial {{HariTrialTersisa}} hari.</p>',
            'Aktif' => true,
        ], $ganti);
    }
}
