<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\ValueObjects;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Kredensial satu penyedia yang sudah didekripsi, hanya hidup di memori (PRD 8.23).
 *
 * Jangan diserialisasi, dicatat ke log, atau dikirim ke peramban.
 */
final readonly class KredensialPenyedia
{
    /** @param  array<string, string>  $nilai */
    public function __construct(
        public KategoriPenyediaLayanan $kategori,
        public string $kode,
        public bool $modeUji,
        private array $nilai,
    ) {}

    public function ambil(string $kunci): string
    {
        $isi = $this->nilai[$kunci] ?? '';

        if ($isi === '') {
            throw new AturanBisnisDilanggar("Isian {$kunci} untuk penyedia {$this->kode} belum diatur di konsol platform.");
        }

        return $isi;
    }

    public function ambilAtau(string $kunci, string $bawaan = ''): string
    {
        $isi = $this->nilai[$kunci] ?? '';

        return $isi === '' ? $bawaan : $isi;
    }

    /** @return array<string, never> */
    public function __debugInfo(): array
    {
        return [];
    }
}
