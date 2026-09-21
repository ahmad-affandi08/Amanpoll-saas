<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Enums;

enum KanalNotifikasi: string
{
    case InApp = 'InApp';
    case Email = 'Email';
}
