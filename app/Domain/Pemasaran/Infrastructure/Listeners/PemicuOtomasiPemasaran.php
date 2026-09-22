<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Infrastructure\Listeners;

use App\Domain\Pemasaran\Application\Actions\MulaiEksekusiOtomasi;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EksekusiOtomasiPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Jobs\ProsesOtomasiPemasaran;

/** Menyalakan otomasi dari EventPemasaran yang baru ditulis, lewat observer agar penulis mana pun tetap memicunya (MARKETING.md 17). */
final class PemicuOtomasiPemasaran
{
    public function __construct(private readonly MulaiEksekusiOtomasi $mulai) {}

    public function created(EventPemasaran $event): void
    {
        foreach ($this->mulai->dariEvent($event) as $eksekusi) {
            if ($eksekusi->Status->siapDiproses()) {
                $this->antrekan($eksekusi);
            }
        }
    }

    private function antrekan(EksekusiOtomasiPemasaran $eksekusi): void
    {
        ProsesOtomasiPemasaran::dispatch($eksekusi->Id);
    }
}
