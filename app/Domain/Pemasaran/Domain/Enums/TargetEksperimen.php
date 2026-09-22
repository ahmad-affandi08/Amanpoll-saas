<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Apa yang diuji satu eksperimen (MARKETING.md 22). */
enum TargetEksperimen: string
{
    case Headline = 'Headline';
    case Cta = 'Cta';
    case SeksiLanding = 'SeksiLanding';
    case PanjangFormulir = 'PanjangFormulir';
    case PresentasiHarga = 'PresentasiHarga';
    case NaskahOnboarding = 'NaskahOnboarding';
    case SubjekEmail = 'SubjekEmail';
}
