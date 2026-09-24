<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\Contracts\PenyediaWhatsApp;
use App\Shared\Domain\Exceptions\DataTidakDitemukan;
use LogicException;

/** Adapter WhatsApp yang terpasang, dicari menurut kodenya (MARKETING.md 16, 28). */
final class RegistriPenyediaWhatsApp
{
    /** @var array<string, PenyediaWhatsApp> */
    private array $penyedia = [];

    /** @param list<PenyediaWhatsApp> $penyedia */
    public function __construct(array $penyedia)
    {
        foreach ($penyedia as $satu) {
            $kode = $satu->kode();

            if (isset($this->penyedia[$kode])) {
                throw new LogicException("Penyedia WhatsApp {$kode} sudah terdaftar.");
            }

            $this->penyedia[$kode] = $satu;
        }
    }

    public function ada(string $kode): bool
    {
        return isset($this->penyedia[$kode]);
    }

    public function untuk(string $kode): PenyediaWhatsApp
    {
        return $this->penyedia[$kode]
            ?? throw new DataTidakDitemukan("Penyedia WhatsApp {$kode} tidak terdaftar.");
    }
}
