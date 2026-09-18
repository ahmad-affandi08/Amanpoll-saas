<?php

declare(strict_types=1);

namespace App\Shared\Domain\Contracts;

interface TransaksiDatabase
{
    public function jalankan(callable $callback): mixed;
}
