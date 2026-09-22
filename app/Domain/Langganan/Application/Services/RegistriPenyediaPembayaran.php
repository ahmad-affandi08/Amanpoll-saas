<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Application\Services;

use App\Domain\Langganan\Domain\Contracts\PenyediaPembayaran;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use LogicException;

/** Daftar penyedia pembayaran yang terpasang (22.06). */
final class RegistriPenyediaPembayaran
{
    /** @var array<string, PenyediaPembayaran> */
    private array $penyedia = [];

    public function daftarkan(PenyediaPembayaran $penyedia): void
    {
        $kode = $penyedia->kode();

        if (isset($this->penyedia[$kode])) {
            throw new LogicException("Penyedia pembayaran {$kode} sudah terdaftar.");
        }

        $this->penyedia[$kode] = $penyedia;
    }

    public function ada(string $kode): bool
    {
        return isset($this->penyedia[$kode]);
    }

    public function untuk(string $kode): PenyediaPembayaran
    {
        return $this->penyedia[$kode]
            ?? throw new DataTidakDitemukan("Penyedia pembayaran {$kode} tidak terdaftar.");
    }

    /** @return array<string, PenyediaPembayaran> */
    public function semua(): array
    {
        return $this->penyedia;
    }

    public function bawaan(): PenyediaPembayaran
    {
        $kode = (string) config('amanpoll.langganan.penyedia_pembayaran', 'TransferManual');

        return $this->untuk($kode);
    }
}
