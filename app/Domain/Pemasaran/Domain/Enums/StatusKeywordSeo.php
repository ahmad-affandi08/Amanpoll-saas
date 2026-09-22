<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Enums;

/** Sejauh mana satu keyword sudah digarap (MARKETING.md 9). */
enum StatusKeywordSeo: string
{
    case Ide = 'Ide';
    case Ditargetkan = 'Ditargetkan';
    case Dikerjakan = 'Dikerjakan';
    case Terbit = 'Terbit';
    case Ditunda = 'Ditunda';
}
