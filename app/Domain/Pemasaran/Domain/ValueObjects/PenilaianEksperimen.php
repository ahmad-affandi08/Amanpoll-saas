<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

use App\Domain\Pemasaran\Infrastructure\Persistence\Models\VarianEksperimen;

/** Jawaban atas satu pertanyaan: bolehkah pemenang dinyatakan sekarang (MARKETING.md 22). */
final readonly class PenilaianEksperimen
{
    /** @param array<string, int> $peserta Jumlah peserta tiap varian, agar alasannya dapat ditelusuri. */
    public function __construct(
        public bool $bolehDinyatakan,
        public ?VarianEksperimen $pemenang,
        public ?string $alasan = null,
        public array $peserta = [],
    ) {}
}
