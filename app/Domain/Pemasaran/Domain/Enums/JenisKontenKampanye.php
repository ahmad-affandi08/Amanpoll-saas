<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Bentuk materi yang ditautkan ke satu kampanye (MARKETING.md 13). */
enum JenisKontenKampanye: string
{
    case Halaman = 'Halaman';
    case Formulir = 'Formulir';
    case Email = 'Email';
    case Iklan = 'Iklan';
    case Sosial = 'Sosial';
    case Artikel = 'Artikel';
    case Lainnya = 'Lainnya';
}
