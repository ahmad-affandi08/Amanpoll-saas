<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;

/** Delapan peristiwa yang dilacak di dalam demo (MARKETING.md 11). */
enum JenisEventDemo: string
{
    case DemoDimulai = 'DemoDimulai';
    case FiturDibuka = 'FiturDibuka';
    case AsetDilihat = 'AsetDilihat';
    case PerintahKerjaDibuat = 'PerintahKerjaDibuat';
    case QrDilihat = 'QrDilihat';
    case PreventifDilihat = 'PreventifDilihat';
    case DemoSelesai = 'DemoSelesai';
    case CtaDiklik = 'CtaDiklik';

    /** Peristiwa yang ikut ditulis ke EventPemasaran; sisanya hanya hidup di dalam demo. */
    public function peristiwaPemasaran(): ?string
    {
        return match ($this) {
            self::DemoDimulai => KatalogPeristiwaPemasaran::DEMO_DIMULAI,
            self::DemoSelesai => KatalogPeristiwaPemasaran::DEMO_SELESAI,
            self::CtaDiklik => KatalogPeristiwaPemasaran::CTA_DIKLIK,
            default => null,
        };
    }

    /** Peristiwa batas sesi yang hanya boleh ditulis layanan sesi, bukan dikirim penjelajah. */
    public function ditulisLayanan(): bool
    {
        return $this === self::DemoDimulai || $this === self::DemoSelesai;
    }
}
