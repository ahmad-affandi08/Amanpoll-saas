<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Enums;

/** Siapa yang mengantar notifikasi email atau WhatsApp (PRD 8.23). */
enum SumberPenyediaNotifikasi: string
{
    /** Penyedia milik Amanpoll; WhatsApp lewat sini memakan kuota bawaan paket. */
    case Platform = 'Platform';

    /** Email atau nomor WhatsApp milik organisasi sendiri; tidak memakan kuota. */
    case Organisasi = 'Organisasi';
}
