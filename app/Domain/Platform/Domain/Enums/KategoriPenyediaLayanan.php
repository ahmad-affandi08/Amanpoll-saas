<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Enums;

/** Jenis layanan luar yang penyedianya dipilih dari konsol platform (PRD 8.23). */
enum KategoriPenyediaLayanan: string
{
    case Pembayaran = 'Pembayaran';
    case WhatsApp = 'WhatsApp';

    public function label(): string
    {
        return match ($this) {
            self::Pembayaran => 'Payment gateway',
            self::WhatsApp => 'WhatsApp',
        };
    }

    /** Pembayaran boleh punya banyak penyedia aktif (tenant memilih); WhatsApp hanya satu yang dipakai. */
    public function bolehBanyakAktif(): bool
    {
        return $this === self::Pembayaran;
    }
}
