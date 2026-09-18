<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

final class DataTidakDitemukan extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 404;
    }

    public function kodeError(): string
    {
        return 'DATA_TIDAK_DITEMUKAN';
    }
}
