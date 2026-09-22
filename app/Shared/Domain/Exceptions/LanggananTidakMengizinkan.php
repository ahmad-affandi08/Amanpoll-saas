<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

/** Permintaan ditolak karena langganan tenant. */
final class LanggananTidakMengizinkan extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 402;
    }

    public function kodeError(): string
    {
        return 'LANGGANAN_TIDAK_MENGIZINKAN';
    }
}
