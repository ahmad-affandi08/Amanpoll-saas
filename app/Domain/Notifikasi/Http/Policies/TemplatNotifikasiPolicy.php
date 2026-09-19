<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class TemplatNotifikasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function update(Pengguna $pengguna, TemplatNotifikasi $templatNotifikasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function delete(Pengguna $pengguna, TemplatNotifikasi $templatNotifikasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
