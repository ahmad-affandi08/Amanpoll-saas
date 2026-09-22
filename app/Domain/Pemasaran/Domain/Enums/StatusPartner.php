<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Status keanggotaan partner; hanya yang aktif boleh masuk portal dan mengirim lead (MARKETING.md 21). */
enum StatusPartner: string
{
    case Diajukan = 'Diajukan';
    case Aktif = 'Aktif';
    case Ditangguhkan = 'Ditangguhkan';
    case Berhenti = 'Berhenti';

    public function bolehMasuk(): bool
    {
        return $this === self::Aktif;
    }

    /**
     * Komisi tetap lahir untuk partner yang ditangguhkan: pembayarannya nyata dan
     * lead-nya sudah dikirim jauh sebelum penangguhan.
     */
    public function berhakKomisi(): bool
    {
        return $this !== self::Berhenti;
    }

    /** @return list<string> */
    public static function nilai(): array
    {
        return array_column(self::cases(), 'value');
    }
}
