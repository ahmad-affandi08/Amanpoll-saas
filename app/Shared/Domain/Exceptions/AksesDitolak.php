<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

final class AksesDitolak extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 403;
    }

    public function kodeError(): string
    {
        return 'AKSES_DITOLAK';
    }
}
