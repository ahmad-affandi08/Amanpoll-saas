<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\DatasetDemo;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Daftar tertutup dataset demo; kode yang tidak terdaftar ditolak saat disimpan (MARKETING.md 11). */
final class RegistriDatasetDemo
{
    /** @var array<string, DatasetDemo> */
    private array $dataset = [];

    /** @param iterable<DatasetDemo> $dataset */
    public function __construct(iterable $dataset = [])
    {
        foreach ($dataset as $satu) {
            $this->dataset[$satu->kode()] = $satu;
        }
    }

    public function ada(string $kode): bool
    {
        return array_key_exists($kode, $this->dataset);
    }

    public function ambil(string $kode): DatasetDemo
    {
        if (! $this->ada($kode)) {
            throw new AturanBisnisDilanggar("Dataset demo {$kode} tidak dikenal.");
        }

        return $this->dataset[$kode];
    }

    /** @return list<string> */
    public function kode(): array
    {
        return array_keys($this->dataset);
    }

    /** @return array<string, string> */
    public function nama(): array
    {
        return array_map(fn (DatasetDemo $satu): string => $satu->nama(), $this->dataset);
    }
}
