<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\TindakanOtomasi;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Daftar tertutup aksi otomasi; kode yang tidak terdaftar ditolak saat disimpan (MARKETING.md 17). */
final class RegistriTindakanOtomasi
{
    /** @var array<string, TindakanOtomasi> */
    private array $tindakan = [];

    /** @param iterable<TindakanOtomasi> $tindakan */
    public function __construct(iterable $tindakan = [])
    {
        foreach ($tindakan as $satu) {
            $this->daftarkan($satu);
        }
    }

    public function daftarkan(TindakanOtomasi $tindakan): void
    {
        $this->tindakan[$tindakan->kode()] = $tindakan;
    }

    public function ada(string $kode): bool
    {
        return array_key_exists($kode, $this->tindakan);
    }

    public function ambil(string $kode): TindakanOtomasi
    {
        if (! $this->ada($kode)) {
            throw new AturanBisnisDilanggar("Aksi otomasi {$kode} tidak dikenal.");
        }

        return $this->tindakan[$kode];
    }

    /** @return list<string> */
    public function kode(): array
    {
        return array_keys($this->tindakan);
    }

    /** @return array<string, string> */
    public function label(): array
    {
        return array_map(fn (TindakanOtomasi $satu): string => $satu->label(), $this->tindakan);
    }
}
