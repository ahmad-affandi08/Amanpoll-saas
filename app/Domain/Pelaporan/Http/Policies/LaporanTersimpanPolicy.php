<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Kepemilikan laporan tersimpan (21.03).
 *
 * Laporan bertanda Pribadi hanya terlihat pemiliknya.
 */
final class LaporanTersimpanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, LaporanTersimpan $laporan): bool
    {
        if ($pengguna->Status !== 'Aktif') {
            return false;
        }

        if ($laporan->PemilikId === $pengguna->Id) {
            return true;
        }

        return ! $laporan->Pribadi && $this->izin->boleh($pengguna->Id, 'Laporan.Lihat');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function update(Pengguna $pengguna, LaporanTersimpan $laporan): bool
    {
        return $pengguna->Status === 'Aktif' && $laporan->PemilikId === $pengguna->Id;
    }

    public function delete(Pengguna $pengguna, LaporanTersimpan $laporan): bool
    {
        return $this->update($pengguna, $laporan);
    }
}
