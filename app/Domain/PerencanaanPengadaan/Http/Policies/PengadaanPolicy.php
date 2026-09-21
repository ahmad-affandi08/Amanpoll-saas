<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Database\Eloquent\Model;

final class PengadaanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->boleh($pengguna);
    }

    public function view(Pengguna $pengguna, Model $model): bool
    {
        return $this->boleh($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->boleh($pengguna);
    }

    public function update(Pengguna $pengguna, Model $model): bool
    {
        return $this->boleh($pengguna);
    }

    public function delete(Pengguna $pengguna, Model $model): bool
    {
        return $this->boleh($pengguna);
    }

    private function boleh(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }
}
