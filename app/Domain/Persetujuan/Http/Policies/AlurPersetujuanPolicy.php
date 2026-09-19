<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class AlurPersetujuanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }

    public function update(Pengguna $pengguna, AlurPersetujuan $alurPersetujuan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }

    public function delete(Pengguna $pengguna, AlurPersetujuan $alurPersetujuan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }
}
