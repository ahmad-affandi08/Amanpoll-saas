<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pemasaran;

use App\Domain\Pemasaran\Application\Services\LayananKonsen;
use App\Domain\Pemasaran\Application\Services\LayananOtomasiPemasaran;
use App\Domain\Pemasaran\Application\Services\PenempelTagProspek;
use App\Domain\Pemasaran\Application\Services\PengevaluasiKondisiOtomasi;
use App\Domain\Pemasaran\Application\Services\PenjalanOtomasi;
use App\Domain\Pemasaran\Domain\Enums\AlasanSupresi;
use App\Domain\Pemasaran\Domain\Enums\OperatorKondisi;
use App\Domain\Pemasaran\Domain\Enums\StatusEksekusiOtomasi;
use App\Domain\Pemasaran\Domain\KatalogKondisiOtomasi;
use App\Domain\Pemasaran\Domain\ValueObjects\KonteksOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanEmailPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/** Evaluasi kondisi otomasi (MARKETING.md 17). */
final class EvaluasiKondisiOtomasiTest extends KasusOtomasi
{
    public function test_skor_dibandingkan_sebagai_angka(): void
    {
        $prospek = $this->buatProspek();
        $prospek->Skor = 55;
        $prospek->save();

        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::SKOR, OperatorKondisi::LebihDari, 40));
        $this->assertFalse($this->nilai($prospek, KatalogKondisiOtomasi::SKOR, OperatorKondisi::LebihDari, 90));
        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::SKOR, OperatorKondisi::KurangDari, 90));
    }

    /** Angka dibandingkan sebagai angka, bukan sebagai teks: "9" tidak boleh lebih besar dari "10". */
    public function test_perbandingan_angka_bukan_perbandingan_teks(): void
    {
        $prospek = $this->buatProspek();
        $prospek->Skor = 9;
        $prospek->save();

        $this->assertFalse($this->nilai($prospek, KatalogKondisiOtomasi::SKOR, OperatorKondisi::LebihDari, 10));
    }

    public function test_sumber_dibandingkan_tanpa_peduli_besar_kecil_huruf(): void
    {
        $prospek = $this->buatProspek();

        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::SamaDengan, 'website'));
        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::SamaDengan, 'WEBSITE'));
        $this->assertFalse($this->nilai($prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::SamaDengan, 'iklan'));
    }

    public function test_salah_satu_dari_menerima_daftar(): void
    {
        $prospek = $this->buatProspek();

        $this->assertTrue($this->nilai(
            $prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::SalahSatuDari, ['Iklan', 'Website'],
        ));
        $this->assertFalse($this->nilai(
            $prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::SalahSatuDari, ['Iklan', 'Partner'],
        ));
    }

    public function test_tag_diperiksa_terhadap_seluruh_isinya(): void
    {
        $prospek = $this->buatProspek();
        app(PenempelTagProspek::class)->tempel($prospek, ['prioritas', 'manufaktur']);

        $segar = $prospek->fresh(['tag']);
        $this->assertNotNull($segar);

        $this->assertTrue($this->nilai($segar, KatalogKondisiOtomasi::TAG, OperatorKondisi::Mengandung, 'manufaktur'));
        $this->assertFalse($this->nilai($segar, KatalogKondisiOtomasi::TAG, OperatorKondisi::Mengandung, 'retail'));
        $this->assertTrue($this->nilai($segar, KatalogKondisiOtomasi::TAG, OperatorKondisi::TidakSamaDengan, 'retail'));
    }

    public function test_consent_terbaca_dari_layanan_konsen(): void
    {
        $prospek = $this->buatProspek();

        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::CONSENT, OperatorKondisi::SamaDengan, 'Ya'));

        app(LayananKonsen::class)->cabut((string) $prospek->Email, AlasanSupresi::Unsubscribe, $prospek);

        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::CONSENT, OperatorKondisi::SamaDengan, 'Tidak'));
    }

    public function test_ada_dan_tidak_ada_membaca_kekosongan(): void
    {
        $prospek = $this->buatProspek();

        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::SUMBER, OperatorKondisi::Ada, null));
        $this->assertTrue($this->nilai($prospek, KatalogKondisiOtomasi::PAKET, OperatorKondisi::TidakAda, null));
        $this->assertFalse($this->nilai($prospek, KatalogKondisiOtomasi::PAKET, OperatorKondisi::Ada, null));
    }

    /** Nilai yang tidak diketahui tidak pernah cocok: otomasi lebih baik diam daripada salah sasaran. */
    public function test_nilai_kosong_tidak_pernah_cocok(): void
    {
        $prospek = $this->buatProspek();

        $this->assertFalse($this->nilai(
            $prospek, KatalogKondisiOtomasi::STATUS_TRIAL, OperatorKondisi::TidakSamaDengan, 'Konversi',
        ));
        $this->assertFalse($this->nilai(
            $prospek, KatalogKondisiOtomasi::JUMLAH_ASET, OperatorKondisi::KurangDari, 5,
        ));
    }

    public function test_aktivitas_terakhir_dihitung_dalam_hari(): void
    {
        $prospek = $this->buatProspek();
        $prospek->AktivitasTerakhirPada = CarbonImmutable::now()->subDays(10);
        $prospek->save();

        $this->assertTrue($this->nilai(
            $prospek, KatalogKondisiOtomasi::AKTIVITAS_TERAKHIR_HARI, OperatorKondisi::LebihDari, 7,
        ));
        $this->assertFalse($this->nilai(
            $prospek, KatalogKondisiOtomasi::AKTIVITAS_TERAKHIR_HARI, OperatorKondisi::LebihDari, 30,
        ));
    }

    public function test_seluruh_kondisi_dalam_satu_langkah_harus_terpenuhi(): void
    {
        $prospek = $this->buatProspek();
        $prospek->Skor = 50;
        $prospek->save();

        $konteks = new KonteksOtomasi($prospek->fresh(), null, null);
        $evaluasi = app(PengevaluasiKondisiOtomasi::class);

        $this->assertTrue($evaluasi->semuaTerpenuhi([
            ['Bidang' => KatalogKondisiOtomasi::SKOR, 'Operator' => 'LebihDari', 'Nilai' => 10],
            ['Bidang' => KatalogKondisiOtomasi::SUMBER, 'Operator' => 'SamaDengan', 'Nilai' => 'Website'],
        ], $konteks));

        $this->assertFalse($evaluasi->semuaTerpenuhi([
            ['Bidang' => KatalogKondisiOtomasi::SKOR, 'Operator' => 'LebihDari', 'Nilai' => 10],
            ['Bidang' => KatalogKondisiOtomasi::SUMBER, 'Operator' => 'SamaDengan', 'Nilai' => 'Iklan'],
        ], $konteks));
    }

    public function test_bidang_yang_tidak_dikenal_ditolak_saat_disimpan(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'kondisi-asing',
            'Nama' => 'Kondisi Asing',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        app(LayananOtomasiPemasaran::class)->simpanLangkah($this->drafTerakhir($otomasi), null, [
            'Jenis' => 'Kondisi',
            'Urutan' => 0,
            'Konfigurasi' => ['Kondisi' => [
                ['Bidang' => 'WarnaFavorit', 'Operator' => 'SamaDengan', 'Nilai' => 'biru'],
            ]],
        ]);
    }

    public function test_operator_yang_tidak_berlaku_untuk_bidangnya_ditolak(): void
    {
        $otomasi = app(LayananOtomasiPemasaran::class)->simpan(null, [
            'Kode' => 'operator-salah',
            'Nama' => 'Operator Salah',
            'Pemicu' => 'ProspekDibuat',
        ]);

        $this->expectException(AturanBisnisDilanggar::class);

        // LebihDari hanya berlaku untuk bidang angka; Sumber adalah teks.
        app(LayananOtomasiPemasaran::class)->simpanLangkah($this->drafTerakhir($otomasi), null, [
            'Jenis' => 'Kondisi',
            'Urutan' => 0,
            'Konfigurasi' => ['Kondisi' => [
                ['Bidang' => KatalogKondisiOtomasi::SUMBER, 'Operator' => 'LebihDari', 'Nilai' => 3],
            ]],
        ]);
    }

    public function test_kondisi_gagal_menghentikan_eksekusi_tanpa_menjalankan_aksinya(): void
    {
        $template = $this->buatTemplate('otomasi-setelah-kondisi');
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahKondisi([
                ['Bidang' => KatalogKondisiOtomasi::SKOR, 'Operator' => 'LebihDari', 'Nilai' => 1000],
            ]),
            $this->langkahAksi('KirimEmail', ['TemplateKode' => $template->Kode]),
        ]);

        $this->buatProspek();
        app(PenjalanOtomasi::class)->jalankan(EksekusiOtomasiPemasaran::query()->firstOrFail());

        $this->assertSame(
            StatusEksekusiOtomasi::BerhentiKondisi,
            EksekusiOtomasiPemasaran::query()->firstOrFail()->Status,
        );
        $this->assertSame(0, PengirimanEmailPemasaran::query()->count());
    }

    public function test_kondisi_terpenuhi_meneruskan_ke_aksinya(): void
    {
        $template = $this->buatTemplate('otomasi-lolos-kondisi');
        $this->buatOtomasi('ProspekDibuat', [
            $this->langkahKondisi([
                ['Bidang' => KatalogKondisiOtomasi::CONSENT, 'Operator' => 'SamaDengan', 'Nilai' => 'Ya'],
            ]),
            $this->langkahAksi('KirimEmail', ['TemplateKode' => $template->Kode]),
        ]);

        $this->buatProspek();
        app(PenjalanOtomasi::class)->jalankan(EksekusiOtomasiPemasaran::query()->firstOrFail());

        $this->assertSame(
            StatusEksekusiOtomasi::Selesai,
            EksekusiOtomasiPemasaran::query()->firstOrFail()->Status,
        );
        $this->assertSame(1, PengirimanEmailPemasaran::query()->count());
    }

    private function nilai(
        Prospek $prospek,
        string $bidang,
        OperatorKondisi $operator,
        mixed $pembanding,
    ): bool {
        return app(PengevaluasiKondisiOtomasi::class)->terpenuhi(
            ['Bidang' => $bidang, 'Operator' => $operator->value, 'Nilai' => $pembanding],
            new KonteksOtomasi($prospek, $prospek->OrganisasiId, null),
        );
    }
}
