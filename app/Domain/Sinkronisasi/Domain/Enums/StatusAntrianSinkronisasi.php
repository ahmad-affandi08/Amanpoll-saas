<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Domain\Enums;

/** Siklus hidup satu mutasi offline di AntrianSinkronisasi (20.03). */
enum StatusAntrianSinkronisasi: string
{
    case Menunggu = 'Menunggu';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Gagal = 'Gagal';
    case Konflik = 'Konflik';
    case Dibatalkan = 'Dibatalkan';

    /** Status akhir yang tidak boleh diproses ulang oleh worker. */
    public function final(): bool
    {
        return in_array($this, [self::Selesai, self::Dibatalkan], true);
    }

    /** Status yang masih menunggu keputusan atau percobaan berikutnya. */
    public function belumTuntas(): bool
    {
        return ! $this->final();
    }
}
