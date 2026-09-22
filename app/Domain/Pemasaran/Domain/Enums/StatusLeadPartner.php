<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Perjalanan satu lead kiriman partner; statusnya hanya boleh maju (MARKETING.md 21). */
enum StatusLeadPartner: string
{
    case Dikirim = 'Dikirim';
    case Diterima = 'Diterima';
    case Ditolak = 'Ditolak';
    case Trial = 'Trial';
    case Paid = 'Paid';

    public function urutan(): int
    {
        return match ($this) {
            self::Dikirim => 1,
            self::Diterima => 2,
            self::Trial => 3,
            self::Paid => 4,
            self::Ditolak => 0,
        };
    }

    public function final(): bool
    {
        return $this === self::Ditolak;
    }

    /** Lead yang ditolak tidak pernah berbuah komisi, sekalipun organisasinya kelak membayar. */
    public function berhakKomisi(): bool
    {
        return $this !== self::Ditolak;
    }

    /** @return list<string> Status yang tidak pernah berbuah komisi. */
    public static function nilaiTanpaKomisi(): array
    {
        return array_values(array_map(
            fn (self $satu): string => $satu->value,
            array_filter(self::cases(), fn (self $satu): bool => ! $satu->berhakKomisi()),
        ));
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
