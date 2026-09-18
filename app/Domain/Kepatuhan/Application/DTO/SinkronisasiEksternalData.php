<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Application\DTO;

final readonly class SinkronisasiEksternalData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $IntegrasiEksternalId = null,
        public mixed $JenisProses = null,
        public mixed $Arah = null,
        public mixed $Status = null,
        public mixed $JumlahData = null,
        public mixed $JumlahBerhasil = null,
        public mixed $JumlahGagal = null,
        public mixed $PesanKesalahan = null,
        public mixed $MulaiPada = null,
        public mixed $SelesaiPada = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            IntegrasiEksternalId: $data['IntegrasiEksternalId'] ?? null,
            JenisProses: $data['JenisProses'] ?? null,
            Arah: $data['Arah'] ?? null,
            Status: $data['Status'] ?? null,
            JumlahData: $data['JumlahData'] ?? null,
            JumlahBerhasil: $data['JumlahBerhasil'] ?? null,
            JumlahGagal: $data['JumlahGagal'] ?? null,
            PesanKesalahan: $data['PesanKesalahan'] ?? null,
            MulaiPada: $data['MulaiPada'] ?? null,
            SelesaiPada: $data['SelesaiPada'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
