<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

/** Dilempar saat optimistic locking mendeteksi data sudah berubah sejak versi yang dibaca client (mis. */
final class VersiDataBerubah extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 409;
    }

    public function kodeError(): string
    {
        return 'VERSI_DATA_BERUBAH';
    }
}
