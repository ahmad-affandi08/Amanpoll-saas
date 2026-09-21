<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Contracts;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;

/**
 * Abstraksi tarik/dorong data (19.03). Setiap jenis sistem eksternal boleh
 * punya adapter sendiri; layanan sinkronisasi hanya mengenal kontrak ini
 * sehingga penambahan sistem baru tidak mengubah alur antrean dan pencatatan.
 */
interface AdapterSinkronisasi
{
    /**
     * Menarik data dari sistem eksternal ke Amanpoll.
     *
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function tarik(IntegrasiEksternal $integrasi, string $jenisProses): array;

    /**
     * Mendorong data Amanpoll ke sistem eksternal.
     *
     * @return array{berhasil: int<0, max>, gagal: int<0, max>}
     */
    public function dorong(IntegrasiEksternal $integrasi, string $jenisProses): array;
}
