<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\DTO;

final readonly class ButirTemplatDaftarPeriksaData
{
    public function __construct(
        public mixed $Id = null,
        public mixed $OrganisasiId = null,
        public mixed $TemplatDaftarPeriksaId = null,
        public mixed $Urutan = null,
        public mixed $Kode = null,
        public mixed $Pertanyaan = null,
        public mixed $TipeJawaban = null,
        public mixed $Satuan = null,
        public mixed $Wajib = null,
        public mixed $NilaiMinimum = null,
        public mixed $NilaiMaksimum = null,
        public mixed $Pilihan = null,
        public mixed $BuktiFotoWajib = null,
        public mixed $MemicuTemuanJika = null,
    ) {}

    public static function dariArray(array $data): self
    {
        return new self(
            Id: $data['Id'] ?? null,
            OrganisasiId: $data['OrganisasiId'] ?? null,
            TemplatDaftarPeriksaId: $data['TemplatDaftarPeriksaId'] ?? null,
            Urutan: $data['Urutan'] ?? null,
            Kode: $data['Kode'] ?? null,
            Pertanyaan: $data['Pertanyaan'] ?? null,
            TipeJawaban: $data['TipeJawaban'] ?? null,
            Satuan: $data['Satuan'] ?? null,
            Wajib: $data['Wajib'] ?? null,
            NilaiMinimum: $data['NilaiMinimum'] ?? null,
            NilaiMaksimum: $data['NilaiMaksimum'] ?? null,
            Pilihan: $data['Pilihan'] ?? null,
            BuktiFotoWajib: $data['BuktiFotoWajib'] ?? null,
            MemicuTemuanJika: $data['MemicuTemuanJika'] ?? null,
        );
    }

    public function keArray(): array
    {
        return array_filter(get_object_vars($this), static fn (mixed $nilai): bool => $nilai !== null);
    }
}
