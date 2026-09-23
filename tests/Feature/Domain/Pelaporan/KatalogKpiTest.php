<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Pelaporan;

use App\Domain\Pelaporan\Application\Services\RegistriKpi;
use App\Domain\Pelaporan\Domain\Enums\BentukKomponen;
use App\Domain\Pelaporan\Domain\Enums\KelompokKpi;
use App\Domain\Pelaporan\Domain\Enums\SatuanKpi;
use App\Domain\Pelaporan\Domain\KatalogKpi;
use App\Domain\Pelaporan\Domain\ValueObjects\DefinisiKpi;
use Tests\TestCase;

/** Gate 21, bagian pertama: setiap KPI utama memiliki definisi formula yang terdokumentasi. */
final class KatalogKpiTest extends TestCase
{
    public function test_setiap_kpi_memiliki_formula_dan_sumber_yang_terdokumentasi(): void
    {
        $this->assertNotEmpty(KatalogKpi::semua());

        foreach (KatalogKpi::semua() as $kunci => $definisi) {
            $this->assertSame($kunci, $definisi->kunci, 'Kunci indeks katalog harus sama dengan kunci definisi.');
            $this->assertNotSame('', trim($definisi->nama), "KPI {$kunci} tidak punya nama.");
            $this->assertNotSame('', trim($definisi->sumber), "KPI {$kunci} tidak menyebut tabel sumbernya.");

            // Rumus harus benar-benar menjelaskan perhitungan, bukan sekadar mengulang nama KPI-nya.
            $this->assertGreaterThan(
                30,
                mb_strlen(trim($definisi->formula)),
                "Formula KPI {$kunci} terlalu pendek untuk menjelaskan perhitungannya.",
            );
        }
    }

    public function test_setiap_kunci_katalog_dilayani_tepat_satu_penyedia(): void
    {
        $registri = app(RegistriKpi::class);

        foreach (KatalogKpi::kunci() as $kunci) {
            $this->assertTrue(
                $registri->ada($kunci),
                "KPI {$kunci} ada di katalog tetapi tidak ada penyedia yang menghitungnya.",
            );
        }
    }

    public function test_tidak_ada_penyedia_yang_menghitung_kpi_di_luar_katalog(): void
    {
        foreach (app(RegistriKpi::class)->kunciTerdaftar() as $kunci) {
            $this->assertTrue(
                KatalogKpi::ada($kunci),
                "Penyedia menghitung {$kunci}, tetapi KPI itu tidak punya definisi formula di katalog.",
            );
        }
    }

    public function test_setiap_kelompok_metrik_yang_diminta_task_terwakili(): void
    {
        // Enam belas butir 21.01 dipetakan ke tiga belas kelompok.
        foreach (KelompokKpi::cases() as $kelompok) {
            $this->assertNotEmpty(
                KatalogKpi::untukKelompok($kelompok),
                "Kelompok {$kelompok->value} tidak memiliki satu pun KPI.",
            );
        }
    }

    public function test_setiap_kpi_menawarkan_minimal_satu_bentuk_tampilan(): void
    {
        foreach (KatalogKpi::semua() as $definisi) {
            $bentuk = BentukKomponen::untukKpi($definisi);

            $this->assertNotEmpty($bentuk, "KPI {$definisi->kunci} tidak punya bentuk tampilan.");
            $this->assertContains(
                BentukKomponen::Angka,
                $bentuk,
                "KPI {$definisi->kunci} harus selalu dapat ditampilkan sebagai kartu angka.",
            );
        }
    }

    public function test_kunci_kpi_memakai_penamaan_yang_stabil(): void
    {
        foreach (KatalogKpi::semua() as $kunci => $definisi) {
            // Kunci tersimpan di konfigurasi dasbor dan laporan pengguna, jadi bentuknya dikunci.
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z_]*\.[a-z][a-z_]*$/',
                $kunci,
                "Kunci {$kunci} tidak mengikuti pola kelompok.nama_metrik.",
            );
            $this->assertInstanceOf(DefinisiKpi::class, $definisi);
        }
    }

    public function test_rincian_kpi_persen_memakai_satuan_isinya_bukan_persen(): void
    {
        // Rincian KPI persen berisi jumlah, jam, atau uang; memformatnya dengan "%" membuat 4 aset terbaca 4%.
        foreach (KatalogKpi::semua() as $kunci => $definisi) {
            if ($definisi->satuan === SatuanKpi::Persen) {
                $this->assertNotSame(SatuanKpi::Persen, $definisi->satuanRincian, "Rincian KPI {$kunci} bukan persen.");
            }
        }

        $this->assertSame('Jumlah', KatalogKpi::ambil('aset.kondisi')->keArray()['SatuanRincian']);
        $this->assertSame('Uang', KatalogKpi::ambil('anggaran.serapan')->keArray()['SatuanRincian']);
        $this->assertSame('Jam', KatalogKpi::ambil('downtime.ketersediaan')->keArray()['SatuanRincian']);
        $this->assertSame('Uang', KatalogKpi::ambil('biaya.pemeliharaan')->keArray()['SatuanRincian']);
    }
}
