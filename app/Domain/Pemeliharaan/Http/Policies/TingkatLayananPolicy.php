<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class TingkatLayananPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->viewAny($pengguna);
    }

    public function update(Pengguna $pengguna, TingkatLayanan $tingkatLayanan): bool
    {
        return $this->viewAny($pengguna);
    }

    public function delete(Pengguna $pengguna, TingkatLayanan $tingkatLayanan): bool
    {
        return $this->viewAny($pengguna);
    }
}
