<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\DTO;

final readonly class DefinisiKolomKustomData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $JenisEntitas = null,
        public mixed $Kode = null,
        public mixed $Label = null,
        public mixed $TipeData = null,
        public mixed $Wajib = null,
        public mixed $Pilihan = null,
        public mixed $AturanValidasi = null,
        public mixed $NilaiBawaan = null,
        public mixed $Urutan = null,
        public mixed $Aktif = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            Kode: $data['Kode'] ?? null,
            Label: $data['Label'] ?? null,
            TipeData: $data['TipeData'] ?? null,
            Wajib: $data['Wajib'] ?? null,
            Pilihan: $data['Pilihan'] ?? null,
            AturanValidasi: $data['AturanValidasi'] ?? null,
            NilaiBawaan: $data['NilaiBawaan'] ?? null,
            Urutan: $data['Urutan'] ?? null,
            Aktif: $data['Aktif'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
