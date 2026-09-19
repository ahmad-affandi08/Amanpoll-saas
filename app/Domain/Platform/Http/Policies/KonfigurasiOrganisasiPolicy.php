<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KonfigurasiOrganisasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function update(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
