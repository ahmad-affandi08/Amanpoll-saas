<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Setelan trial yang berlaku (MARKETING.md 12). */
final readonly class KonfigurasiTrial
{
    public function __construct(
        public int $durasiHari,
        public ?string $paketId,
        public ?string $namaPaket,
        public bool $kartuDiperlukan,
        public ?float $batasPengguna,
        public ?float $batasLokasi,
        public ?float $batasAset,
        public int $hariTenggang,
        public int $perpanjanganMaksHari,
    ) {}

    /** @return array<string, mixed> */
    public function keArray(): array
    {
        return [
            'DurasiHari' => $this->durasiHari,
            'PaketId' => $this->paketId,
            'NamaPaket' => $this->namaPaket,
            'KartuDiperlukan' => $this->kartuDiperlukan,
            'BatasPengguna' => $this->batasPengguna,
            'BatasLokasi' => $this->batasLokasi,
            'BatasAset' => $this->batasAset,
            'HariTenggang' => $this->hariTenggang,
            'PerpanjanganMaksHari' => $this->perpanjanganMaksHari,
        ];
    }
}
