<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\ValueObjects;

/**
 * Satu isian formulir kredensial penyedia (PRD 8.23).
 *
 * Isian `rahasia` tidak pernah dikirim balik ke peramban; mengosongkannya saat
 * menyimpan berarti mempertahankan nilai lama.
 */
final readonly class IsianKredensial
{
    /** @param  list<string>  $pilihan */
    public function __construct(
        public string $kunci,
        public string $label,
        public bool $rahasia = false,
        public bool $wajib = true,
        public ?string $petunjuk = null,
        public array $pilihan = [],
        public ?string $bawaan = null,
    ) {}

    /** @return array{Kunci: string, Label: string, Rahasia: bool, Wajib: bool, Petunjuk: string|null, Pilihan: list<string>, Bawaan: string|null} */
    public function keArray(): array
    {
        return [
            'Kunci' => $this->kunci,
            'Label' => $this->label,
            'Rahasia' => $this->rahasia,
            'Wajib' => $this->wajib,
            'Petunjuk' => $this->petunjuk,
            'Pilihan' => $this->pilihan,
            'Bawaan' => $this->bawaan,
        ];
    }
}
