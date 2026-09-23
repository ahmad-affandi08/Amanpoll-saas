<?php

declare(strict_types=1);

namespace App\Domain\Kodefikasi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;

/**
 * Kodefikasi barang milik negara/daerah adalah urusan penatausahaan aset, jadi
 * dijaga izin Aset.Ubah -- bukan izin ASPAK yang urusannya pelaporan Kemenkes.
 */
final class KodefikasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Lihat');
    }

    public function view(Pengguna $pengguna, Model $model): bool
    {
        return $this->viewAny($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function update(Pengguna $pengguna, Model $model): bool
    {
        return $this->create($pengguna);
    }

    public function delete(Pengguna $pengguna, Model $model): bool
    {
        return $this->create($pengguna);
    }
}
