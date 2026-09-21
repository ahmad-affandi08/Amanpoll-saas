<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\ValueObjects;

/**
 * Hasil satu KPI.
 *
 * `rincian` adalah baris penyusun angka utama — kategori, tren per periode,
 * atau daftar entitas. Ini yang membuat angka dapat ditelusuri kembali ke
 * sumber transaksinya (PRD 8.18) dan yang dipakai grafik serta ekspor, supaya
 * tidak ada bagan yang digambar dari angka karangan.
 */
final readonly class HasilKpi
{
    /**
     * @param  array<int, array<string, mixed>>  $rincian
     * @param  array<string, mixed>  $konteks  angka pendukung, mis. pembilang dan penyebut
     */
    public function __construct(
        public float $nilai,
        public array $rincian = [],
        public array $konteks = [],
    ) {}

    public static function kosong(): self
    {
        return new self(0.0);
    }

    /**
     * Persentase yang aman terhadap penyebut nol: tanpa data, nilainya nol dan
     * konteksnya menyatakan bahwa tidak ada yang diukur, bukan "100%".
     *
     * @param  array<int, array<string, mixed>>  $rincian
     */
    public static function persen(float $pembilang, float $penyebut, array $rincian = []): self
    {
        $nilai = $penyebut > 0 ? round($pembilang / $penyebut * 100, 1) : 0.0;

        return new self($nilai, $rincian, [
            'Pembilang' => $pembilang,
            'Penyebut' => $penyebut,
            'AdaData' => $penyebut > 0,
        ]);
    }

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Nilai' => $this->nilai,
            'Rincian' => $this->rincian,
            'Konteks' => $this->konteks,
        ];
    }
}
