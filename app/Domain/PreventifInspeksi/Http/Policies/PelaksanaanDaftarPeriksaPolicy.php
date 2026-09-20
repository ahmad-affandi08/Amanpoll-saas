<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;

final class PelaksanaanDaftarPeriksaPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, PelaksanaanDaftarPeriksa $pelaksanaan): bool
    {
        return $this->dapatAkses($pengguna, $pelaksanaan);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function update(Pengguna $pengguna, PelaksanaanDaftarPeriksa $pelaksanaan): bool
    {
        return $this->dapatAkses($pengguna, $pelaksanaan);
    }

    public function delete(Pengguna $pengguna, PelaksanaanDaftarPeriksa $pelaksanaan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }

    private function dapatAkses(Pengguna $pengguna, PelaksanaanDaftarPeriksa $pelaksanaan): bool
    {
        if ($this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola')) {
            return true;
        }

        if ($pelaksanaan->DilaksanakanOleh === $pengguna->Id) {
            return true;
        }

        if ($pelaksanaan->PerintahKerjaId !== null && $pelaksanaan->perintahKerja !== null) {
            return $pelaksanaan->perintahKerja->penugasan()
                ->where('PenggunaId', $pengguna->Id)
                ->exists();
        }

        return false;
    }
}
