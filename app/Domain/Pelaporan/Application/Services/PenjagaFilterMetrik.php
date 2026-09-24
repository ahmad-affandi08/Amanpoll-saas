<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Application\Services;

use App\Domain\Pelaporan\Domain\ValueObjects\FilterMetrik;
use App\Domain\Platform\Application\Services\OpsiUnitPengelola;

/**
 * Membuang nilai filter unit pengelola yang bukan unit pengelola organisasi ini (PRD 8.21).
 *
 * Yang sah adalah pilihan penyaring laporan: unit bertanda Mengelola Aset milik
 * organisasi yang berlaku, termasuk yang kini nonaktif (tiket lama bisa saja
 * milik unit itu). Id unit organisasi biasa, unit organisasi lain, atau teks
 * karangan diabaikan -- filter kembali ke "semua unit pengelola" dan layar
 * menampilkan filter yang benar-benar berlaku, bukan pilihan yang tak pernah
 * menyaring apa pun. Organisasi tanpa unit pengelola selalu mendapat filter kosong.
 */
final class PenjagaFilterMetrik
{
    public function bersihkan(FilterMetrik $filter): FilterMetrik
    {
        if (! $filter->adaFilterUnitPengelola()) {
            return $filter;
        }

        return $filter->denganUnitPengelola(array_values(array_intersect(
            $filter->unitPengelolaId,
            $this->idSah(),
        )));
    }

    /** @return list<string> */
    public function idSah(): array
    {
        return array_column(OpsiUnitPengelola::daftar(termasukNonaktif: true), 'Id');
    }

    /** @return list<array{Id: string, Kode: string, Nama: string}> */
    public function pilihan(): array
    {
        return OpsiUnitPengelola::daftar(termasukNonaktif: true);
    }
}
