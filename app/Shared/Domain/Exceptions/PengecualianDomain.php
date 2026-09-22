<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

use RuntimeException;

/** Basis seluruh exception domain Amanpoll. */
abstract class PengecualianDomain extends RuntimeException
{
    abstract public function kodeStatusHttp(): int;

    abstract public function kodeError(): string;
}
