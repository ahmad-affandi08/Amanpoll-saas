<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

enum StatusSerahTerimaAset: string
{
    case Diserahkan = 'Diserahkan';
    case Diterima = 'Diterima';
}
