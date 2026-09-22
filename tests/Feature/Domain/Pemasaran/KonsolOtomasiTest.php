<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananOtomasiPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusOtomasi;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\LangkahOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\OtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VersiOtomasiPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Konsol otomasi: izin, versi, dan validasi langkah (MARKETING.md 17, 26). */
final class KonsolOtomasiTest extends KasusOtomasi
{
    private const AKAR = '/admin-platform/pemasaran/otomasi';

    public function test_melihat_konsol_otomasi_menuntut_izin(): void
    {
        $this->aktingSebagai([])->get(self::AKAR)->assertForbidden();
        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_LIHAT])->get(self::AKAR)->assertOk();
    }

    public function test_izin_kelola_tidak_cukup_untuk_mengaktifkan(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['x']]),
        ], aktifkan: false);
        $versi = $this->drafTerakhir($otomasi);

        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_KELOLA])
            ->post(self::AKAR."/{$otomasi->Kode}/versi/{$versi->Id}/aktifkan")
            ->assertForbidden();

        $this->assertSame(StatusOtomasi::Draf, $versi->fresh()?->Status);
    }

    public function test_izin_aktifkan_dapat_mengaktifkan_versinya(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['x']]),
        ], aktifkan: false);
        $versi = $this->drafTerakhir($otomasi);

        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_AKTIFKAN])
            ->post(self::AKAR."/{$otomasi->Kode}/versi/{$versi->Id}/aktifkan")
            ->assertRedirect();

        $this->assertSame(StatusOtomasi::Aktif, $versi->fresh()?->Status);
        $this->assertSame($versi->Id, $otomasi->fresh()?->VersiAktifId);
    }

    /** Versi yang sudah diaktifkan dikunci; eksekusi yang berjalan memakainya apa adanya. */
    public function test_versi_aktif_tidak_dapat_disunting(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['x']]),
        ]);
        $versi = VersiOtomasiPemasaran::query()->findOrFail($otomasi->VersiAktifId);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpanLangkah($versi, null, [
            'Jenis' => 'Jeda',
            'Urutan' => 1,
            'Konfigurasi' => ['Menit' => 10],
        ]);
    }

    public function test_draf_baru_menyalin_langkah_versi_aktif(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahJeda(30),
            $this->langkahAksi('TambahTag', ['Tag' => ['x']]),
        ]);

        $draf = app(LayananOtomasiPemasaran::class)->buatVersi($otomasi, salinVersiAktif: true);

        $this->assertSame(StatusOtomasi::Draf, $draf->Status);
        $this->assertSame(2, LangkahOtomasiPemasaran::query()
            ->where('VersiOtomasiPemasaranId', $draf->Id)
            ->count());
    }

    public function test_mengaktifkan_versi_baru_mengarsipkan_yang_lama(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['lama']]),
        ]);
        $lama = VersiOtomasiPemasaran::query()->findOrFail($otomasi->VersiAktifId);

        $layanan = app(LayananOtomasiPemasaran::class);
        $baru = $layanan->buatVersi($otomasi, salinVersiAktif: true);
        $layanan->aktifkan($baru);

        $this->assertSame(StatusOtomasi::Diarsipkan, $lama->fresh()?->Status);
        $this->assertSame($baru->Id, $otomasi->fresh()?->VersiAktifId);
    }

    public function test_konfigurasi_aksi_yang_tidak_sah_ditolak_saat_disimpan(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'aksi-tanpa-template',
            'Nama' => 'Aksi Tanpa Template',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpanLangkah($this->drafTerakhir($otomasi), null, [
            'Jenis' => 'Aksi',
            'Urutan' => 0,
            'Konfigurasi' => ['Aksi' => 'KirimEmail', 'Konfigurasi' => ['TemplateKode' => 'tidak-ada']],
        ]);
    }

    public function test_aksi_yang_tidak_terdaftar_ditolak(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'aksi-asing',
            'Nama' => 'Aksi Asing',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpanLangkah($this->drafTerakhir($otomasi), null, [
            'Jenis' => 'Aksi',
            'Urutan' => 0,
            'Konfigurasi' => ['Aksi' => 'LuncurkanRoket', 'Konfigurasi' => []],
        ]);
    }

    public function test_jeda_di_luar_batas_ditolak(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'jeda-nol',
            'Nama' => 'Jeda Nol',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpanLangkah($this->drafTerakhir($otomasi), null, [
            'Jenis' => 'Jeda',
            'Urutan' => 0,
            'Konfigurasi' => ['Menit' => 0],
        ]);
    }

    public function test_kode_otomasi_ganda_ditolak(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_KELOLA])
            ->post(self::AKAR, $this->isiOtomasi())
            ->assertRedirect();

        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_KELOLA])
            ->post(self::AKAR, $this->isiOtomasi())
            ->assertSessionHasErrors('Kode');

        $this->assertSame(1, OtomasiPemasaran::query()->where('Kode', 'sapa-baru')->count());
    }

    public function test_pemicu_di_luar_katalog_ditolak_lewat_http(): void
    {
        $this->aktingSebagai([KatalogIzinPemasaran::OTOMASI_KELOLA])
            ->post(self::AKAR, $this->isiOtomasi(['Pemicu' => 'ProspekDibikin']))
            ->assertSessionHasErrors('Pemicu');
    }

    /** @param list<string> $izin */
    private function aktingSebagai(array $izin): self
    {
        $this->actingAs($this->buatAdmin($izin), 'platform');

        return $this;
    }

    /** @param array<string, mixed> $ganti */
    private function isiOtomasi(array $ganti = []): array
    {
        return array_merge([
            'Kode' => 'sapa-baru',
            'Nama' => 'Sapa Prospek Baru',
            'Pemicu' => 'ProspekDibuat',
        ], $ganti);
    }
}
