<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Domain\Contracts;

use App\Domain\Pelaporan\Domain\Enums\FormatEkspor;

/** Penulis berkas ekspor untuk satu format (21.05). */
interface PenulisEkspor
{
    public function format(): FormatEkspor;

    /**
     * @param  list<string>  $kepala
     * @param  list<list<string|float>>  $baris
     * @param  array<string, string>  $meta  keterangan yang dicetak di berkas, mis. judul dan rentang
     */
    public function tulis(string $pathLokal, array $kepala, array $baris, array $meta): void;
}
