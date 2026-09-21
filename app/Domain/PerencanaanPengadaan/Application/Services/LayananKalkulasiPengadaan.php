<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Application\Services;

use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\ValueObjects\Uang;

final class LayananKalkulasiPengadaan
{
    public function totalBaris(string $jumlah, string $hargaSatuan, string $diskon = '0', string $pajak = '0'): string
    {
        $jumlahSkalaEmpat = $this->desimalKeInteger($jumlah, 4);
        $hargaMinor = Uang::dariString($hargaSatuan)->nilaiMinor();
        $subtotalMinor = intdiv(($jumlahSkalaEmpat * $hargaMinor) + 5000, 10000);
        $totalMinor = $subtotalMinor - Uang::dariString($diskon)->nilaiMinor() + Uang::dariString($pajak)->nilaiMinor();

        if ($jumlahSkalaEmpat <= 0 || $hargaMinor < 0 || $totalMinor < 0) {
            throw new AturanBisnisDilanggar('Jumlah, harga, diskon, atau pajak menghasilkan total baris yang tidak valid.');
        }

        return Uang::dariMinor($totalMinor)->keString();
    }

    /**
     * @param  iterable<string>  $nilai
     */
    public function jumlahkan(iterable $nilai): string
    {
        $total = Uang::nol();
        foreach ($nilai as $uang) {
            $total = $total->tambah(Uang::dariString($uang));
        }

        return $total->keString();
    }

    private function desimalKeInteger(string $nilai, int $skala): int
    {
        $nilai = trim($nilai);
        if (! preg_match('/^\d+(?:\.\d+)?$/', $nilai)) {
            throw new AturanBisnisDilanggar('Nilai desimal tidak valid.');
        }

        [$bulat, $pecahan] = array_pad(explode('.', $nilai, 2), 2, '');

        return ((int) $bulat * (10 ** $skala)) + (int) substr(str_pad($pecahan, $skala, '0'), 0, $skala);
    }
}
