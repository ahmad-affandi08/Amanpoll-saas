<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;
use Tests\TestCase;

class UangTest extends TestCase
{
    public function test_dari_string_mengurai_nilai_desimal_dengan_benar(): void
    {
        $uang = Uang::dariString('1234.56');

        $this->assertSame('1234.56', $uang->keString());
        $this->assertSame(123456, $uang->nilaiMinor());
    }

    public function test_pembulatan_setengah_ke_atas_saat_presisi_melebihi_skala(): void
    {
        $this->assertSame('10.13', Uang::dariString('10.125')->keString());
        $this->assertSame('10.12', Uang::dariString('10.124')->keString());
        $this->assertSame('11.00', Uang::dariString('10.995')->keString());
    }

    public function test_tambah_dan_kurang_akurat_tanpa_galat_pembulatan_float(): void
    {
        $total = Uang::nol();

        for ($i = 0; $i < 10; $i++) {
            $total = $total->tambah(Uang::dariString('0.10'));
        }

        $this->assertSame('1.00', $total->keString());
    }

    public function test_kali_dengan_kuantitas_integer(): void
    {
        $hargaSatuan = Uang::dariString('15000.00');

        $this->assertSame('45000.00', $hargaSatuan->kali(3)->keString());
    }

    public function test_nilai_negatif_terformat_dengan_benar(): void
    {
        $uang = Uang::dariString('100.00')->kurang(Uang::dariString('150.00'));

        $this->assertSame('-50.00', $uang->keString());
    }

    public function test_operasi_menolak_mata_uang_berbeda(): void
    {
        $this->expectException(AturanBisnisDilanggar::class);

        Uang::dariString('10.00', mataUang: 'IDR')->tambah(Uang::dariString('10.00', mataUang: 'USD'));
    }

    public function test_lebih_besar_dari_membandingkan_nilai_minor(): void
    {
        $this->assertTrue(Uang::dariString('20.00')->lebihBesarDari(Uang::dariString('19.99')));
        $this->assertFalse(Uang::dariString('19.99')->lebihBesarDari(Uang::dariString('20.00')));
    }
}
