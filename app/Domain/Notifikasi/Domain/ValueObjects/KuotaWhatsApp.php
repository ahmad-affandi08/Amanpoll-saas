<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\ValueObjects;

/**
 * Pemakaian kuota WhatsApp bawaan (lewat nomor Amanpoll) sebulan kalender organisasi (PRD 8.23).
 *
 * `batas` null berarti tanpa batas. Paket yang tidak memasukkan WhatsApp bawaan sama
 * sekali (`termasukPaket` false) diperlakukan sebagai kuota yang selalu habis.
 */
final readonly class KuotaWhatsApp
{
    public function __construct(
        public int $terpakai,
        public ?int $batas,
        public bool $termasukPaket,
    ) {}

    public function habis(): bool
    {
        return ! $this->termasukPaket || ($this->batas !== null && $this->terpakai >= $this->batas);
    }

    /** null berarti tanpa batas. */
    public function sisa(): ?int
    {
        if (! $this->termasukPaket) {
            return 0;
        }

        return $this->batas === null ? null : max(0, $this->batas - $this->terpakai);
    }

    /** @return array{Terpakai: int, Batas: int|null, Sisa: int|null, TermasukPaket: bool, Habis: bool} */
    public function keArray(): array
    {
        return [
            'Terpakai' => $this->terpakai,
            'Batas' => $this->batas,
            'Sisa' => $this->sisa(),
            'TermasukPaket' => $this->termasukPaket,
            'Habis' => $this->habis(),
        ];
    }
}
