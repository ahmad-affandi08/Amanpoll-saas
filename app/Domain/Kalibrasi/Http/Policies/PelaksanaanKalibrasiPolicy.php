<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class PelaksanaanKalibrasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, PelaksanaanKalibrasi $pelaksanaanKalibrasi): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola');
    }

    public function update(Pengguna $pengguna, PelaksanaanKalibrasi $pelaksanaanKalibrasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola') || $pelaksanaanKalibrasi->DilaksanakanOleh === $pengguna->Id;
    }

    public function delete(Pengguna $pengguna, PelaksanaanKalibrasi $pelaksanaanKalibrasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola');
    }
}
