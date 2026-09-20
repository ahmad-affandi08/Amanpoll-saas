<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;

final class RencanaPemeliharaanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, RencanaPemeliharaan $rencana): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }

    public function update(Pengguna $pengguna, RencanaPemeliharaan $rencana): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }

    public function delete(Pengguna $pengguna, RencanaPemeliharaan $rencana): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }
}
