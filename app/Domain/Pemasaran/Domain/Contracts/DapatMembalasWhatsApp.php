<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\Contracts;

/** Penyedia yang dapat membalas pesan masuk dengan teks bebas di dalam jendela percakapan (MARKETING.md 16). */
interface DapatMembalasWhatsApp
{
    /** Mengembalikan pengenal pesan balasan di sisi penyedia. */
    public function balas(string $kepada, string $teks): string;
}
