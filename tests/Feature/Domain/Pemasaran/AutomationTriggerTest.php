<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananOtomasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenjalanOtomasi;
use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPemicuOtomasi;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\TagProspek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Pemicu otomasi dan apa yang menyalakannya (MARKETING.md 17). */
final class AutomationTriggerTest extends KasusOtomasi
{
    public function test_peristiwa_menyalakan_otomasi_yang_memicunya(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['prospek-baru']]),
        ]);

        $prospek = $this->buatProspek();
        $this->jalankanSemua();

        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();
        $this->assertSame($otomasi->Id, $eksekusi->OtomasiPemasaranId);
        $this->assertSame($prospek->Id, $eksekusi->ProspekId);
        $this->assertSame(StatusEksekusiOtomasi::Selesai, $eksekusi->Status);
        $this->assertSame(['prospek-baru'], $this->tag($prospek->Id));
    }

    public function test_peristiwa_lain_tidak_menyalakannya(): void
    {
        $this->buatOtomasi('TrialBerakhir', [
            $this->langkahAksi('TambahTag', ['Tag' => ['trial-habis']]),
        ]);

        $this->buatProspek();

        $this->assertSame(0, EksekusiOtomasiPemasaran::query()->count());
    }

    public function test_otomasi_nonaktif_tidak_menyalakan_apa_pun(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['diam']]),
        ]);
        app(LayananOtomasiPemasaran::class)->ubahAktif($otomasi, false);

        $this->buatProspek();

        $this->assertSame(0, EksekusiOtomasiPemasaran::query()->count());
    }

    public function test_otomasi_tanpa_versi_aktif_tidak_dapat_dinyalakan(): void
    {
        $otomasi = $this->buatOtomasi('ProspekDibuat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['x']]),
        ], aktifkan: false);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->ubahAktif($otomasi, true);
    }

    public function test_versi_tanpa_langkah_tidak_dapat_diaktifkan(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'kosong',
            'Nama' => 'Kosong',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->aktifkan($this->drafTerakhir($otomasi));
    }

    public function test_pemicu_yang_tidak_dikenal_ditolak(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'salah-pemicu',
            'Nama' => 'Salah',
            'Pemicu' => 'ProspekDibikin',
        ]);
    }

    /** Pemicu yang belum ada sumbernya tetap boleh disimpan, tetapi jujur dinyatakan belum berlaku. */
    public function test_pemicu_tanpa_sumber_tersimpan_tetapi_tidak_pernah_menyala(): void
    {
        $this->assertTrue(KatalogPemicuOtomasi::dikenal('ReferralTerdaftar'));
        $this->assertFalse(KatalogPemicuOtomasi::berlaku('ReferralTerdaftar'));

        $this->buatOtomasi('ReferralTerdaftar', [
            $this->langkahAksi('TambahTag', ['Tag' => ['referral']]),
        ]);

        $this->buatProspek();
        $this->jalankanSemua();

        $this->assertSame(0, EksekusiOtomasiPemasaran::query()->count());
    }

    /** Seluruh pemicu yang dinyatakan berlaku harus benar-benar punya peristiwa yang dikenal. */
    public function test_tiap_pemicu_berlaku_menunjuk_peristiwa_yang_dikenal(): void
    {
        foreach (KatalogPemicuOtomasi::kode() as $kode) {
            $peristiwa = KatalogPemicuOtomasi::peristiwa($kode);

            if ($peristiwa !== null) {
                $this->assertTrue(
                    KatalogPeristiwaPemasaran::dikenal($peristiwa),
                    "Pemicu {$kode} menunjuk peristiwa {$peristiwa} yang tidak ada di taxonomy.",
                );
            }
        }
    }

    public function test_dua_otomasi_pada_pemicu_sama_keduanya_berjalan(): void
    {
        $this->buatOtomasi('ProspekDibuat', [$this->langkahAksi('TambahTag', ['Tag' => ['satu']])]);
        $this->buatOtomasi('ProspekDibuat', [$this->langkahAksi('TambahTag', ['Tag' => ['dua']])]);

        $prospek = $this->buatProspek();
        $this->jalankanSemua();

        $this->assertSame(2, EksekusiOtomasiPemasaran::query()->count());
        $this->assertEqualsCanonicalizing(['satu', 'dua'], $this->tag($prospek->Id));
    }

    public function test_peristiwa_tanpa_prospek_tidak_menggagalkan_diam_diam(): void
    {
        $this->buatOtomasi('HalamanHargaDilihat', [
            $this->langkahAksi('TambahTag', ['Tag' => ['lihat-harga']]),
        ]);

        app(PerekamEventPemasaran::class)->catat(KatalogPeristiwaPemasaran::HARGA_DILIHAT);
        $this->jalankanSemua();

        $eksekusi = EksekusiOtomasiPemasaran::query()->firstOrFail();
        $this->assertSame(StatusEksekusiOtomasi::Gagal, $eksekusi->Status);
        $this->assertStringContainsString('prospek', (string) $eksekusi->Galat);
    }

    private function jalankanSemua(): void
    {
        foreach (EksekusiOtomasiPemasaran::query()->get() as $eksekusi) {
            if (! $eksekusi->Status->final()) {
                app(PenjalanOtomasi::class)->jalankan($eksekusi);
            }
        }
    }

    /** @return list<string> */
    private function tag(string $prospekId): array
    {
        return array_values(TagProspek::query()
            ->join('ProspekTag', 'ProspekTag.TagProspekId', '=', 'TagProspek.Id')
            ->where('ProspekTag.ProspekId', $prospekId)
            ->pluck('TagProspek.Nama')
            ->all());
    }
}
