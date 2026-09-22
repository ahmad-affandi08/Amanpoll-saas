<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use App\Domain\Langganan\Domain\Enums\StatusPembayaranLangganan;

/** Bentuk baku sebuah peristiwa pembayaran, apa pun penyedianya (22.06). */
final readonly class PeristiwaPembayaran
{
    /**
     * @param  string  $idPeristiwa  Identitas peristiwa di sisi penyedia; inilah
     *                               kunci idempotensi, jadi ia wajib stabil saat
     *                               peristiwa yang sama dikirim ulang.
     * @param  array<string, mixed>  $muatanMentah
     */
    public function __construct(
        public string $idPeristiwa,
        public string $nomorTagihan,
        public float $jumlah,
        public StatusPembayaranLangganan $status,
        public ?string $referensiEksternal = null,
        public ?string $metode = null,
        public array $muatanMentah = [],
    ) {}
}
