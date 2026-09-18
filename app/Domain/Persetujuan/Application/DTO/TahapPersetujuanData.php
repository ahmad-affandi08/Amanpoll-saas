<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\DTO;

final readonly class TahapPersetujuanData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $AlurPersetujuanId = null,
        public mixed $Urutan = null,
        public mixed $Nama = null,
        public mixed $JenisPenyetuju = null,
        public mixed $PeranId = null,
        public mixed $PenggunaId = null,
        public mixed $JumlahMinimumPenyetuju = null,
        public mixed $BolehMenyetujuiSendiri = null,
        public mixed $BatasWaktuMenit = null,
        public mixed $Kondisi = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            AlurPersetujuanId: $data['AlurPersetujuanId'] ?? null,
            Urutan: $data['Urutan'] ?? null,
            Nama: $data['Nama'] ?? null,
            JenisPenyetuju: $data['JenisPenyetuju'] ?? null,
            PeranId: $data['PeranId'] ?? null,
            PenggunaId: $data['PenggunaId'] ?? null,
            JumlahMinimumPenyetuju: $data['JumlahMinimumPenyetuju'] ?? null,
            BolehMenyetujuiSendiri: $data['BolehMenyetujuiSendiri'] ?? null,
            BatasWaktuMenit: $data['BatasWaktuMenit'] ?? null,
            Kondisi: $data['Kondisi'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
