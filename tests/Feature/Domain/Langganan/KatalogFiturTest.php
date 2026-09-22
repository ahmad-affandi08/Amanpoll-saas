<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Langganan;

use App\Domain\Langganan\Domain\Enums\TipeBatasFitur;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use Database\Seeders\FiturPaketSeeder;

/** Master fitur dan kestabilan kodenya (22.01). */
final class KatalogFiturTest extends KasusLangganan
{
    public function test_setiap_fitur_katalog_punya_master_di_basis_data(): void
    {
        foreach (KatalogFitur::kode() as $kode) {
            $this->assertNotNull(
                FiturPaket::query()->where('Kode', $kode)->first(),
                "Fitur {$kode} ada di katalog tetapi belum disemai sebagai master.",
            );
        }
    }

    public function test_tidak_ada_master_fitur_di_luar_katalog(): void
    {
        foreach (FiturPaket::query()->pluck('Kode') as $kode) {
            $this->assertTrue(
                KatalogFitur::ada((string) $kode),
                "Master fitur {$kode} tidak punya definisi di katalog.",
            );
        }
    }

    public function test_setiap_fitur_punya_nama_dan_deskripsi_yang_menjelaskan(): void
    {
        foreach (KatalogFitur::semua() as $kode => $definisi) {
            $this->assertSame($kode, $definisi->kode);
            $this->assertNotSame('', trim($definisi->nama), "Fitur {$kode} tidak punya nama.");
            $this->assertGreaterThan(
                20,
                mb_strlen(trim($definisi->deskripsi)),
                "Deskripsi fitur {$kode} terlalu pendek untuk menjelaskan apa yang dibeli pelanggan.",
            );
        }
    }

    public function test_kode_fitur_memakai_penamaan_yang_stabil(): void
    {
        foreach (KatalogFitur::kode() as $kode) {
            // Kode tertanam di rute dan di baris PaketFitur setiap pelanggan, jadi bentuknya dikunci.
            $this->assertMatchesRegularExpression('/^[a-z][a-z_]*\.[a-z][a-z_]*$/', $kode);
        }
    }

    public function test_menyemai_ulang_tidak_mengganti_id_master(): void
    {
        $sebelum = FiturPaket::query()->orderBy('Kode')->pluck('Id', 'Kode')->all();

        $this->seed(FiturPaketSeeder::class);

        $sesudah = FiturPaket::query()->orderBy('Kode')->pluck('Id', 'Kode')->all();

        // Id yang berubah akan memutus entitlement seluruh pelanggan.
        $this->assertSame($sebelum, $sesudah);
    }

    public function test_fitur_batas_bertipe_angka_dan_fitur_modul_bertipe_boolean(): void
    {
        $this->assertSame(
            TipeBatasFitur::Angka,
            KatalogFitur::ambil(KatalogFitur::BATAS_ASET)->tipeBatas,
        );
        $this->assertSame(
            TipeBatasFitur::Boolean,
            KatalogFitur::ambil(KatalogFitur::MODUL_KALIBRASI)->tipeBatas,
        );
    }

    public function test_modul_berbayar_bawaannya_tertutup(): void
    {
        foreach (KatalogFitur::bertipe(TipeBatasFitur::Boolean) as $definisi) {
            // Paket yang lupa menyebut modul tidak boleh diam-diam membukanya.
            $this->assertFalse(
                $definisi->diizinkanBawaan,
                "Modul {$definisi->kode} terbuka secara bawaan; paket yang lupa menyebutnya akan gratis.",
            );
        }
    }
}
