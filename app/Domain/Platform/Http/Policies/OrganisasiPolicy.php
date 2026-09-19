<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Organisasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class OrganisasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function view(Pengguna $pengguna, Organisasi $organisasi): bool
    {
        return true;
    }

    public function update(Pengguna $pengguna, Organisasi $organisasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
