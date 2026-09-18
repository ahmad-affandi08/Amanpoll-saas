<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KunciApi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KunciApiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Integrasi.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Integrasi.Kelola');
    }

    public function delete(Pengguna $pengguna, KunciApi $kunciApi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Integrasi.Kelola');
    }
}
