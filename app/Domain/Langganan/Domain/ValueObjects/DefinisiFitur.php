<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\ValueObjects;

use App\Domain\Langganan\Domain\Enums\TipeBatasFitur;

/** Satu entri katalog fitur paket (22.01). */
final readonly class DefinisiFitur
{
    public function __construct(
        public string $kode,
        public string $nama,
        public string $deskripsi,
        public TipeBatasFitur $tipeBatas,
        /** Nilai bawaan saat sebuah paket belum menyebut fitur ini sama sekali. */
        public bool $diizinkanBawaan = false,
        public ?float $batasBawaan = null,
        /** Satuan batas, dipakai untuk menyusun pesan penolakan yang jelas. */
        public ?string $satuanBatas = null,
    ) {}

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'Kode' => $this->kode,
            'Nama' => $this->nama,
            'Deskripsi' => $this->deskripsi,
            'TipeBatas' => $this->tipeBatas->value,
            'LabelTipeBatas' => $this->tipeBatas->label(),
            'DiizinkanBawaan' => $this->diizinkanBawaan,
            'BatasBawaan' => $this->batasBawaan,
            'SatuanBatas' => $this->satuanBatas,
        ];
    }
}
