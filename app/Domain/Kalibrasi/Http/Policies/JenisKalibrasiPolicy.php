<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class JenisKalibrasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, JenisKalibrasi $jenisKalibrasi): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola');
    }

    public function update(Pengguna $pengguna, JenisKalibrasi $jenisKalibrasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola');
    }

    public function delete(Pengguna $pengguna, JenisKalibrasi $jenisKalibrasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kalibrasi.Kelola');
    }
}
