<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Domain\Enums;

/**
 * Status satu aset di dalam permintaan mutasi.
 *
 * Disetujui dan Ditolak dipakai saat pemegang aset memutuskan per baris;
 * Menunggu tetap ikut dieksekusi supaya permintaan lama yang tidak melewati
 * keputusan per aset berjalan seperti semula.
 */
enum StatusDetailMutasiAset: string
{
    case Menunggu = 'Menunggu';
    case Disetujui = 'Disetujui';
    case Ditolak = 'Ditolak';
    case Selesai = 'Selesai';
    case Dibatalkan = 'Dibatalkan';

    /** @return list<string> */
    public static function nilaiDapatDieksekusi(): array
    {
        return [self::Menunggu->value, self::Disetujui->value];
    }
}
