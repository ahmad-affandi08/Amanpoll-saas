<?php

declare(strict_types=1);

namespace App\Core\Idempotensi;

use App\Shared\Domain\Exceptions\PengecualianDomain;

/** Kunci idempotensi dipakai ulang untuk muatan yang berbeda. */
final class KonflikIdempotensi extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 409;
    }

    public function kodeError(): string
    {
        return 'KONFLIK_IDEMPOTENSI';
    }
}
