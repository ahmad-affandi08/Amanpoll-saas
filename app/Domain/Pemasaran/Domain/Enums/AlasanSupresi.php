<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Mengapa satu alamat berhenti menerima pesan pemasaran (MARKETING.md 27). */
enum AlasanSupresi: string
{
    case Unsubscribe = 'Unsubscribe';
    case Bounce = 'Bounce';
    case KeluhanSpam = 'KeluhanSpam';
    case Manual = 'Manual';
    case Penghapusan = 'Penghapusan';
}
