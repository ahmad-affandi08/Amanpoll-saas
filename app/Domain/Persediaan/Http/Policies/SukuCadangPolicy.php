<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Menjaga SukuCadang beserta master data pendukungnya (KategoriSukuCadang,
 * KelompokSukuCadang, KompatibilitasSukuCadang) lewat satu kode Izin.
 */
final class SukuCadangPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function view(Pengguna $pengguna, SukuCadang $sukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function update(Pengguna $pengguna, SukuCadang $sukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function delete(Pengguna $pengguna, SukuCadang $sukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }
}
