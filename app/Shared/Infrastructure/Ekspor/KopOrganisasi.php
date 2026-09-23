<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ekspor;

use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;

/**
 * Nama organisasi sebagaimana dicetak di kop berkas ekspor.
 *
 * Dipisahkan karena dipakai dua jalur yang tidak saling memanggil: ekspor
 * daftar yang dialirkan langsung ke peramban, dan ekspor laporan tersimpan
 * yang dibangun di pekerja antrean. Dua berkas yang menyebut rumah sakit yang
 * sama dengan dua bentuk nama yang berbeda akan tampak berasal dari dua sistem
 * bagi yang menerimanya.
 */
final class KopOrganisasi
{
    /** Nama legal ikut disebut bila berbeda, karena itu yang dikenali di luar rumah sakit. */
    public static function nama(?Organisasi $organisasi): string
    {
        $nama = trim((string) ($organisasi->Nama ?? ''));
        $namaLegal = trim((string) ($organisasi->NamaLegal ?? ''));

        if ($namaLegal === '' || $namaLegal === $nama) {
            return $nama;
        }

        return $nama === '' ? $namaLegal : $nama.' ('.$namaLegal.')';
    }
}
