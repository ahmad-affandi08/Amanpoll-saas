<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Enums\ModelAttribution;
use App\Domain\Pemasaran\Domain\ValueObjects\BobotSentuhan;
use App\Domain\Pemasaran\Domain\ValueObjects\Sentuhan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonImmutable;

/**
 * Membagi satu jasa konversi kepada sentuhan-sentuhannya. Pembagiannya
 * dilakukan dalam bilangan bulat, jadi jumlahnya tepat satu pada model mana pun
 * dan tidak bergantung pada pembulatan pecahan (Gate 38.10).
 */
final class PembagiBobotAttribution
{
    /** Porsi tetap sentuhan pertama dan terakhir pada model berbasis posisi. */
    private const PORSI_UJUNG = 0.4;

    /**
     * @param  list<Sentuhan>  $sentuhan  urut dari yang paling awal
     * @return list<BobotSentuhan>
     */
    public function bagi(
        array $sentuhan,
        ModelAttribution $model,
        CarbonImmutable $konversiPada,
        int $paruhHari,
    ): array {
        if ($sentuhan === []) {
            return [];
        }

        $mentah = $this->mentah($sentuhan, $model, $konversiPada, $paruhHari);
        $bagian = $this->bulatkan($mentah);

        $hasil = [];

        foreach ($sentuhan as $urutan => $satu) {
            $hasil[] = new BobotSentuhan($satu, $bagian[$urutan]);
        }

        return $hasil;
    }

    /**
     * Porsi mentah tiap sentuhan sebelum dinormalkan; skalanya bebas.
     *
     * @param  list<Sentuhan>  $sentuhan
     * @return list<float>
     */
    private function mentah(
        array $sentuhan,
        ModelAttribution $model,
        CarbonImmutable $konversiPada,
        int $paruhHari,
    ): array {
        $jumlah = count($sentuhan);

        return match ($model) {
            ModelAttribution::Pertama => $this->hanyaSatu($jumlah, 0),
            ModelAttribution::Terakhir => $this->hanyaSatu($jumlah, $jumlah - 1),
            ModelAttribution::Linear => array_fill(0, $jumlah, 1.0),
            ModelAttribution::TimeDecay => $this->peluruhan($sentuhan, $konversiPada, $paruhHari),
            ModelAttribution::PositionBased => $this->berbasisPosisi($jumlah),
        };
    }

    /** @return list<float> */
    private function hanyaSatu(int $jumlah, int $indeks): array
    {
        $mentah = array_fill(0, $jumlah, 0.0);
        $mentah[$indeks] = 1.0;

        return array_values($mentah);
    }

    /**
     * @param  list<Sentuhan>  $sentuhan
     * @return list<float>
     */
    private function peluruhan(array $sentuhan, CarbonImmutable $konversiPada, int $paruhHari): array
    {
        if ($paruhHari < 1) {
            throw new AturanBisnisDilanggar('Paruh waktu peluruhan harus setidaknya satu hari.');
        }

        $mentah = [];

        foreach ($sentuhan as $satu) {
            // Sentuhan setelah konversi tidak mungkin ada, tetapi jarak negatif tetap dijaga.
            $hari = max(0.0, $satu->pada->diffInDays($konversiPada, true));
            $mentah[] = 2 ** (-$hari / $paruhHari);
        }

        return $mentah;
    }

    /** @return list<float> */
    private function berbasisPosisi(int $jumlah): array
    {
        if ($jumlah <= 2) {
            return array_fill(0, $jumlah, 1.0);
        }

        $tengah = (1.0 - 2 * self::PORSI_UJUNG) / ($jumlah - 2);
        $mentah = array_fill(0, $jumlah, $tengah);
        $mentah[0] = self::PORSI_UJUNG;
        $mentah[$jumlah - 1] = self::PORSI_UJUNG;

        return array_values($mentah);
    }

    /**
     * Metode sisa terbesar: tiap sentuhan mendapat bagian bulatnya, lalu sisa
     * satuan dibagikan kepada pecahan tersisa terbesar. Jumlahnya karena itu
     * selalu tepat SKALA.
     *
     * @param  list<float>  $mentah
     * @return list<int>
     */
    private function bulatkan(array $mentah): array
    {
        $total = array_sum($mentah);

        if ($total <= 0.0) {
            throw new AturanBisnisDilanggar('Bobot seluruh sentuhan nol; tidak ada yang dapat dibagi.');
        }

        $bagian = [];
        $pecahan = [];

        foreach ($mentah as $urutan => $satu) {
            $tepat = $satu / $total * BobotSentuhan::SKALA;
            $bagian[$urutan] = (int) floor($tepat);
            $pecahan[$urutan] = $tepat - $bagian[$urutan];
        }

        $sisa = BobotSentuhan::SKALA - array_sum($bagian);
        $urutanPecahan = array_keys($pecahan);

        // Seri dimenangkan indeks yang lebih awal, supaya hasilnya tidak bergantung urutan pengurutan.
        usort($urutanPecahan, fn (int $a, int $b): int => $pecahan[$b] <=> $pecahan[$a] ?: $a <=> $b);

        foreach (array_slice($urutanPecahan, 0, max(0, $sisa)) as $urutan) {
            $bagian[$urutan]++;
        }

        return array_values($bagian);
    }
}
