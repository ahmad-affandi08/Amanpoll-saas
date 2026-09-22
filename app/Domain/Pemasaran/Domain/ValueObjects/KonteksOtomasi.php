<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\Prospek;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Bahan yang dipakai kondisi dan aksi satu eksekusi otomasi (MARKETING.md 17). */
final readonly class KonteksOtomasi
{
    /** @param array<string, mixed> $dataPeristiwa */
    public function __construct(
        public ?Prospek $prospek,
        public ?string $organisasiId,
        public ?EventPemasaran $event,
        public array $dataPeristiwa = [],
        public string $kunciLangkah = '',
    ) {}

    /** Salinan dengan kunci idempotensi satu langkah; aksi memakainya agar percobaan ulang tidak berlipat. */
    public function untukLangkah(string $kunci): self
    {
        return new self($this->prospek, $this->organisasiId, $this->event, $this->dataPeristiwa, $kunci);
    }

    /** Prospek wajib ada untuk sebagian besar aksi; tanpa itu tidak ada yang bisa disurati. */
    public function wajibProspek(): Prospek
    {
        if ($this->prospek === null) {
            throw new AturanBisnisDilanggar(
                'Langkah ini membutuhkan prospek, sedangkan peristiwanya tidak tertaut ke siapa pun.',
            );
        }

        return $this->prospek;
    }
}
