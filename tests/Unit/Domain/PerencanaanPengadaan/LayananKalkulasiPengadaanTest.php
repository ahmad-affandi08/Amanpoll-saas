<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\PerencanaanPengadaan;

use App\Domain\PerencanaanPengadaan\Application\Services\LayananKalkulasiPengadaan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use PHPUnit\Framework\TestCase;

final class LayananKalkulasiPengadaanTest extends TestCase
{
    private LayananKalkulasiPengadaan $kalkulasi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kalkulasi = new LayananKalkulasiPengadaan;
    }

    public function test_total_baris_menggunakan_aritmetika_integer_tanpa_galat_float(): void
    {
        $this->assertSame('1000000.00', $this->kalkulasi->totalBaris('10', '100000'));
        $this->assertSame('0.30', $this->kalkulasi->totalBaris('3', '0.1'));
        $this->assertSame('105.00', $this->kalkulasi->totalBaris('2.5000', '40', '5', '10'));
    }

    public function test_total_baris_membulatkan_setengah_ke_atas_pada_dua_desimal(): void
    {
        $this->assertSame('1.01', $this->kalkulasi->totalBaris('0.3333', '3.03'));
        $this->assertSame('0.34', $this->kalkulasi->totalBaris('0.3333', '1.01'));
    }

    public function test_total_baris_menolak_jumlah_nol_dan_total_negatif(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);
        $this->kalkulasi->totalBaris('0', '100');
    }

    public function test_total_baris_menolak_diskon_yang_melebihi_subtotal(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);
        $this->kalkulasi->totalBaris('1', '100', '150');
    }

    public function test_total_baris_menolak_nilai_desimal_tidak_valid(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);
        $this->kalkulasi->totalBaris('sepuluh', '100');
    }

    public function test_jumlahkan_menjumlahkan_daftar_nilai_sebagai_uang(): void
    {
        $this->assertSame('0.00', $this->kalkulasi->jumlahkan([]));
        $this->assertSame('11000000.00', $this->kalkulasi->jumlahkan(['1000000.00', '10000000.00']));
        $this->assertSame('0.30', $this->kalkulasi->jumlahkan(['0.10', '0.10', '0.10']));
    }
}
