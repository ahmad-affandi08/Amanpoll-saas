<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exceptions;

/**
 * Permintaan ditolak karena langganan tenant: paketnya tidak memuat fitur itu,
 * kuotanya habis, atau langganannya sudah lewat masa tenggang.
 *
 * Dipisahkan dari AksesDitolak karena penyebabnya berbeda dan jalan keluarnya
 * juga berbeda: yang ini tidak diselesaikan dengan memberi izin pada peran,
 * melainkan dengan membayar atau menaikkan paket.
 */
final class LanggananTidakMengizinkan extends PengecualianDomain
{
    public function kodeStatusHttp(): int
    {
        return 402;
    }

    public function kodeError(): string
    {
        return 'LANGGANAN_TIDAK_MENGIZINKAN';
    }
}
