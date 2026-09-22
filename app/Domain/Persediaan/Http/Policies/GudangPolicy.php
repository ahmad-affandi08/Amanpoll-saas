<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Menjaga Gudang sekaligus LokasiGudang (anak langsung Gudang) -- keduanya dikelola lewat kode Izin yang sama. */
final class GudangPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function view(Pengguna $pengguna, Gudang $gudang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function update(Pengguna $pengguna, Gudang $gudang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function delete(Pengguna $pengguna, Gudang $gudang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }
}
