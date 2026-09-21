<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\Services;

use App\Domain\Kepatuhan\Domain\Contracts\AdapterSinkronisasi;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;

/**
 * Menentukan adapter mana yang dipakai untuk sebuah integrasi. Jenis yang
 * belum punya adapter khusus jatuh ke adapter REST bawaan.
 */
final class RegistriAdapterSinkronisasi
{
    /** @var array<string, AdapterSinkronisasi> */
    private array $adapter = [];

    public function __construct(private readonly AdapterSinkronisasi $bawaan) {}

    public function daftarkan(string $jenis, AdapterSinkronisasi $adapter): void
    {
        $this->adapter[strtolower($jenis)] = $adapter;
    }

    public function untuk(IntegrasiEksternal $integrasi): AdapterSinkronisasi
    {
        return $this->adapter[strtolower($integrasi->Jenis)] ?? $this->bawaan;
    }
}
