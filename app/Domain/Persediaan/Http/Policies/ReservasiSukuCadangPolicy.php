<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Reservasi mengikuti lingkup gudangnya (PRD 8.21): gudang di luar lingkup tidak bisa dilihat, dilepas, atau dipakai. */
final class ReservasiSukuCadangPolicy
{
    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly LingkupGudang $lingkupGudang,
    ) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function view(Pengguna $pengguna, ReservasiSukuCadang $reservasiSukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola')
            && $this->lingkupGudang->terlihat($reservasiSukuCadang->GudangId);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function update(Pengguna $pengguna, ReservasiSukuCadang $reservasiSukuCadang): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola')
            && $this->lingkupGudang->terlihat($reservasiSukuCadang->GudangId);
    }
}
