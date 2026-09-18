<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Application\DTO;

final readonly class AntrianSinkronisasiData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PerangkatPenggunaId = null,
        public mixed $KunciOperasi = null,
        public mixed $JenisEntitas = null,
        public mixed $EntitasId = null,
        public mixed $Operasi = null,
        public mixed $VersiKlien = null,
        public mixed $MuatanData = null,
        public mixed $Status = null,
        public mixed $Konflik = null,
        public mixed $Percobaan = null,
        public mixed $DiterimaPada = null,
        public mixed $DiprosesPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PerangkatPenggunaId: $data['PerangkatPenggunaId'] ?? null,
            KunciOperasi: $data['KunciOperasi'] ?? null,
            JenisEntitas: $data['JenisEntitas'] ?? null,
            EntitasId: $data['EntitasId'] ?? null,
            Operasi: $data['Operasi'] ?? null,
            VersiKlien: $data['VersiKlien'] ?? null,
            MuatanData: $data['MuatanData'] ?? null,
            Status: $data['Status'] ?? null,
            Konflik: $data['Konflik'] ?? null,
            Percobaan: $data['Percobaan'] ?? null,
            DiterimaPada: $data['DiterimaPada'] ?? null,
            DiprosesPada: $data['DiprosesPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
