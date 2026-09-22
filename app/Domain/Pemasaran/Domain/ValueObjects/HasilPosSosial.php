<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Domain\ValueObjects;

/** Jawaban penyedia atas satu posting (MARKETING.md 18, 28). */
final readonly class HasilPosSosial
{
    public function __construct(
        public string $idPost,
        public ?string $url = null,
    ) {}
}
