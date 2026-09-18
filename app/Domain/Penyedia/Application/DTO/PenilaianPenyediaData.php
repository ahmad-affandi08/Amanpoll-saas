<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Application\DTO;

final readonly class PenilaianPenyediaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $PenyediaId = null,
        public mixed $PeriodeMulai = null,
        public mixed $PeriodeSelesai = null,
        public mixed $SkorKualitas = null,
        public mixed $SkorKetepatanWaktu = null,
        public mixed $SkorHarga = null,
        public mixed $SkorLayanan = null,
        public mixed $SkorTotal = null,
        public mixed $Catatan = null,
        public mixed $DinilaiOleh = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            PenyediaId: $data['PenyediaId'] ?? null,
            PeriodeMulai: $data['PeriodeMulai'] ?? null,
            PeriodeSelesai: $data['PeriodeSelesai'] ?? null,
            SkorKualitas: $data['SkorKualitas'] ?? null,
            SkorKetepatanWaktu: $data['SkorKetepatanWaktu'] ?? null,
            SkorHarga: $data['SkorHarga'] ?? null,
            SkorLayanan: $data['SkorLayanan'] ?? null,
            SkorTotal: $data['SkorTotal'] ?? null,
            Catatan: $data['Catatan'] ?? null,
            DinilaiOleh: $data['DinilaiOleh'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
