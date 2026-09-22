<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Persetujuan template oleh penyedia, bukan sakelar kami sendiri (MARKETING.md 16). */
enum StatusPersetujuanTemplateWa: string
{
    case Draf = 'Draf';
    case Diajukan = 'Diajukan';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
    case Ditangguhkan = 'Ditangguhkan';

    /** Hanya template yang benar-benar disetujui penyedia yang boleh berangkat. */
    public function bolehDikirim(): bool
    {
        return $this === self::Disetujui;
    }

    /** @return list<self> */
    public function tujuanSah(): array
    {
        return match ($this) {
            self::Draf => [self::Diajukan],
            self::Diajukan => [self::Disetujui, self::Ditolak],
            self::Disetujui => [self::Ditangguhkan, self::Ditolak],
            self::Ditolak => [self::Draf],
            self::Ditangguhkan => [self::Disetujui, self::Ditolak],
        };
    }

    public function bolehPindahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->tujuanSah(), true);
    }
}
