<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Models;

use App\Domain\Platform\Domain\Enums\KategoriPenyediaLayanan;
use App\Domain\Platform\Domain\ValueObjects\KredensialPenyedia;

/**
 * Pembacaan kolom `KredensialTerenkripsi` (cast `encrypted:array`) yang sama untuk
 * penyedia milik platform dan milik organisasi (PRD 8.23).
 *
 * @property KategoriPenyediaLayanan $Kategori
 * @property string $Kode
 * @property bool $ModeUji
 * @property array<mixed>|null $KredensialTerenkripsi
 */
trait PunyaKredensialPenyedia
{
    /** @return array<string, string> */
    public function nilaiKredensial(): array
    {
        $nilai = [];

        foreach ((array) ($this->KredensialTerenkripsi ?? []) as $kunci => $isi) {
            if (is_string($kunci) && is_scalar($isi)) {
                $nilai[$kunci] = (string) $isi;
            }
        }

        return $nilai;
    }

    public function keKredensial(): KredensialPenyedia
    {
        return new KredensialPenyedia(
            $this->Kategori,
            (string) $this->Kode,
            (bool) $this->ModeUji,
            $this->nilaiKredensial(),
            $this->tempatPengaturan(),
        );
    }

    abstract protected function tempatPengaturan(): string;
}
