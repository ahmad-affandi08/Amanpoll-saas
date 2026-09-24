<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\ValueObjects;

/** Jawaban penyedia saat kredensialnya dicoba dari konsol platform (PRD 8.23). */
final readonly class HasilUjiKoneksi
{
    public function __construct(
        public bool $berhasil,
        public string $pesan,
    ) {}
}
