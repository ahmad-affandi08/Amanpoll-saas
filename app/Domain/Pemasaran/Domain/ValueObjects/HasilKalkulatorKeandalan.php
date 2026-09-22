<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/**
 * Hasil kalkulator keandalan publik. Angka yang tidak punya penyebut dinyatakan
 * belum tersedia beserta alasannya, bukan dijawab nol (MARKETING.md 10).
 */
final readonly class HasilKalkulatorKeandalan
{
    public function __construct(
        public float $menitOperasional,
        public float $totalJam,
        public ?float $mttr,
        public ?float $mtbf,
        public ?float $ketersediaan,
        public ?string $alasanMttr = null,
        public ?string $alasanMtbf = null,
        public ?string $alasanKetersediaan = null,
        public ?string $peringatan = null,
    ) {}

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'MenitOperasional' => $this->menitOperasional,
            'TotalJam' => $this->totalJam,
            'Mttr' => $this->mttr,
            'Mtbf' => $this->mtbf,
            'Ketersediaan' => $this->ketersediaan,
            'AlasanMttr' => $this->alasanMttr,
            'AlasanMtbf' => $this->alasanMtbf,
            'AlasanKetersediaan' => $this->alasanKetersediaan,
            'Peringatan' => $this->peringatan,
        ];
    }
}
