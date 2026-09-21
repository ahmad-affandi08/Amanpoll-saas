<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Application\Actions\KelolaLangganan;
use App\Domain\Langganan\Application\Services\LayananLangganan;
use App\Domain\Langganan\Domain\Enums\SiklusLangganan;
use App\Domain\Langganan\Domain\Enums\StatusLangganan;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/**
 * Siklus hidup langganan (22.04): mulai, uji coba, aktif, tenggang,
 * kedaluwarsa, batal.
 */
final class SiklusHidupLanggananTest extends KasusLangganan
{
    public function test_memulai_langganan_tanpa_uji_coba_langsung_aktif_satu_periode(): void
    {
        $paket = $this->buatPaketLengkap();

        $langganan = $this->aksi()->mulai((string) $this->organisasi->Id, [
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => '2026-06-15',
        ]);

        $this->assertSame(StatusLangganan::Aktif->value, $langganan->Status);
        $this->assertSame('2026-06-15', $langganan->MulaiPada->toDateString());
        $this->assertSame('2026-07-15', $langganan->BerakhirPada->toDateString());
        $this->assertNull($langganan->UjiCobaSampai);
    }

    public function test_uji_coba_tidak_memakan_periode_berbayar(): void
    {
        $paket = $this->buatPaketLengkap();

        $langganan = $this->aksi()->mulai((string) $this->organisasi->Id, [
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => '2026-06-15',
            'UjiCobaSampai' => '2026-06-29',
        ]);

        $this->assertSame(StatusLangganan::UjiCoba->value, $langganan->Status);
        $this->assertSame('2026-06-29', $langganan->UjiCobaSampai->toDateString());
        // Sebulan dihitung dari akhir uji coba, bukan dari tanggal mulai.
        $this->assertSame('2026-07-29', $langganan->BerakhirPada->toDateString());
    }

    public function test_akhir_uji_coba_tidak_boleh_mendahului_tanggal_mulai(): void
    {
        $paket = $this->buatPaketLengkap();

        $this->expectException(AturanBisnisDilanggar::class);

        $this->aksi()->mulai((string) $this->organisasi->Id, [
            'PaketLanggananId' => $paket->Id,
            'Siklus' => SiklusLangganan::Bulanan->value,
            'MulaiPada' => '2026-06-15',
            'UjiCobaSampai' => '2026-06-01',
        ]);
    }

    public function test_status_efektif_melewati_tanggal_akhir_menjadi_tenggang_lalu_kedaluwarsa(): void
    {
        config(['amanpoll.langganan.hari_tenggang' => 7]);
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-06-10');

        $layanan = app(LayananLangganan::class);

        // Di dalam tenggang (10 Juni + 7 hari = 17 Juni).
        $this->assertSame(
            StatusLangganan::Tenggang,
            $layanan->statusEfektif($langganan, CarbonImmutable::parse('2026-06-15')),
        );
        $this->assertSame(
            StatusLangganan::Tenggang,
            $layanan->statusEfektif($langganan, CarbonImmutable::parse('2026-06-17')),
        );
        // Sehari setelah tenggang berakhir.
        $this->assertSame(
            StatusLangganan::Kedaluwarsa,
            $layanan->statusEfektif($langganan, CarbonImmutable::parse('2026-06-18')),
        );
    }

    public function test_status_efektif_tidak_bergantung_pada_kolom_status_yang_tertinggal(): void
    {
        $paket = $this->buatPaketLengkap();
        // Kolomnya masih 'Aktif' walau tanggalnya sudah jauh terlewat: inilah
        // keadaan setelah perintah harian gagal jalan.
        $langganan = $this->buatLangganan(
            $paket,
            StatusLangganan::Aktif,
            berakhirPada: '2026-01-01',
        );

        $this->assertSame(
            StatusLangganan::Kedaluwarsa,
            app(LayananLangganan::class)->statusEfektif($langganan),
        );
    }

    public function test_perpanjangan_lebih_awal_menambah_waktu_bukan_membuang_sisa_periode(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $this->aksi()->perpanjang($langganan, CarbonImmutable::parse('2026-06-20'));

        // Titik tolaknya tanggal akhir yang masih di depan, bukan hari ini.
        $this->assertSame('2026-08-15', $langganan->refresh()->BerakhirPada->toDateString());
    }

    public function test_perpanjangan_setelah_kedaluwarsa_dihitung_dari_hari_ini(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-01-01');

        $this->aksi()->perpanjang($langganan, CarbonImmutable::parse('2026-06-15'));

        $this->assertSame('2026-07-15', $langganan->refresh()->BerakhirPada->toDateString());
    }

    public function test_perpanjangan_mengakhiri_uji_coba(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, StatusLangganan::UjiCoba, ujiCobaSampai: '2026-06-29');

        $this->aksi()->perpanjang($langganan);

        $langganan->refresh();
        $this->assertNull($langganan->UjiCobaSampai);
        $this->assertSame(StatusLangganan::Aktif->value, $langganan->Status);
    }

    public function test_pembatalan_bawaan_berlaku_di_akhir_periode(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $this->aksi()->batalkan($langganan);
        $langganan->refresh();

        $this->assertNotNull($langganan->BatalPada);
        // Yang sudah dibayar tidak dicabut seketika.
        $this->assertSame('2026-07-15', $langganan->BerakhirPada->toDateString());
        $this->assertTrue(app(LayananLangganan::class)->statusEfektif($langganan)->memberiAksesPenuh());
    }

    public function test_pembatalan_segera_langsung_mencabut_akses_tulis(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, berakhirPada: '2026-07-15');

        $this->aksi()->batalkan($langganan, segera: true);
        $langganan->refresh();

        $this->assertSame(StatusLangganan::Dibatalkan->value, $langganan->Status);
        $this->assertFalse(app(LayananLangganan::class)->statusEfektif($langganan)->memberiAksesPenuh());
    }

    public function test_pembatalan_mengunci_status_apa_pun_tanggalnya(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, StatusLangganan::Dibatalkan, berakhirPada: '2026-12-31');

        $this->assertSame(
            StatusLangganan::Dibatalkan,
            app(LayananLangganan::class)->statusEfektif($langganan),
        );
    }

    public function test_mengganti_paket_mengubah_baris_yang_sama_bukan_menambah_baris_kedua(): void
    {
        $lama = $this->buatPaket('Paket Lama');
        $baru = $this->buatPaket('Paket Baru');

        $this->aksi()->mulai((string) $this->organisasi->Id, ['PaketLanggananId' => $lama->Id]);
        $this->aksi()->mulai((string) $this->organisasi->Id, ['PaketLanggananId' => $baru->Id]);

        $baris = Langganan::query()
            ->withoutGlobalScopes()
            ->where('OrganisasiId', $this->organisasi->Id)
            ->get();

        $this->assertCount(1, $baris, 'Dua baris aktif berarti dua jawaban entitlement untuk tenant yang sama.');
        $this->assertSame($baru->Id, (string) $baris[0]->PaketLanggananId);
    }

    public function test_paket_nonaktif_tidak_dapat_dilanggan(): void
    {
        $paket = $this->buatPaketLengkap();
        $paket->update(['Aktif' => false]);

        $this->expectException(AturanBisnisDilanggar::class);

        $this->aksi()->mulai((string) $this->organisasi->Id, ['PaketLanggananId' => $paket->Id]);
    }

    public function test_perintah_penyegar_menyelaraskan_kolom_status(): void
    {
        $paket = $this->buatPaketLengkap();
        $langganan = $this->buatLangganan($paket, StatusLangganan::Aktif, berakhirPada: '2026-01-01');

        $this->artisan('langganan:segarkan-status')->assertSuccessful();

        $this->assertSame(
            StatusLangganan::Kedaluwarsa->value,
            (string) $langganan->refresh()->Status,
        );
    }

    private function aksi(): KelolaLangganan
    {
        return app(KelolaLangganan::class);
    }
}
