<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

final class KonflikData extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 409;
    }

    public function kodeError(): string
    {
        return 'KONFLIK_DATA';
    }
}
