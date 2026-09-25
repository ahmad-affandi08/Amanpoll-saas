<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Enums;

/** Jenis layanan luar yang penyedianya dipilih dari konsol platform (PRD 8.23). */
enum KategoriPenyediaLayanan: string
{
    case Pembayaran = 'Pembayaran';
    case WhatsApp = 'WhatsApp';
    case Email = 'Email';

    public function label(): string
    {
        return match ($this) {
            self::Pembayaran => 'Payment gateway',
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Email',
        };
    }

    /** Pembayaran boleh punya banyak penyedia aktif (tenant memilih); WhatsApp dan email hanya satu yang dipakai. */
    public function bolehBanyakAktif(): bool
    {
        return $this === self::Pembayaran;
    }

    /**
     * Email dan WhatsApp boleh dipasang organisasi untuk notifikasi stafnya sendiri.
     * Pembayaran tidak: gateway itu menerima uang langganan untuk Amanpoll.
     */
    public function bolehMilikOrganisasi(): bool
    {
        return $this !== self::Pembayaran;
    }
}
