<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Enam metrik yang boleh dipakai menilai eksperimen (MARKETING.md 22). */
enum MetrikEksperimen: string
{
    case Ctr = 'Ctr';
    case KonversiFormulir = 'KonversiFormulir';
    case KonversiDemo = 'KonversiDemo';
    case KonversiTrial = 'KonversiTrial';
    case Aktivasi = 'Aktivasi';
    case KonversiBayar = 'KonversiBayar';

    public function label(): string
    {
        return match ($this) {
            self::Ctr => 'CTR',
            self::KonversiFormulir => 'Konversi formulir',
            self::KonversiDemo => 'Konversi demo',
            self::KonversiTrial => 'Konversi trial',
            self::Aktivasi => 'Aktivasi',
            self::KonversiBayar => 'Konversi bayar',
        };
    }

    /** Tahap funnel yang menjadi pembilangnya; null berarti metrik ini punya kueri sendiri. */
    public function tahapFunnel(): ?TahapFunnelGrowth
    {
        return match ($this) {
            self::KonversiDemo => TahapFunnelGrowth::Demo,
            self::KonversiTrial => TahapFunnelGrowth::Trial,
            self::Aktivasi => TahapFunnelGrowth::Activated,
            self::KonversiBayar => TahapFunnelGrowth::Paid,
            default => null,
        };
    }
}
