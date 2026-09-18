<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

final class AturanBisnisDilanggar extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 422;
    }

    public function kodeError(): string
    {
        return 'ATURAN_BISNIS_DILANGGAR';
    }
}
