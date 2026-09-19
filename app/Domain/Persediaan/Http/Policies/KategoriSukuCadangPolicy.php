<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KategoriSukuCadangPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function update(Pengguna $pengguna, KategoriSukuCadang $kategoriSukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function delete(Pengguna $pengguna, KategoriSukuCadang $kategoriSukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }
}
