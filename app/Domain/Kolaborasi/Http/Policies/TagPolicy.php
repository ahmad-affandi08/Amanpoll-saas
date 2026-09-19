<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Tag;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class TagPolicy
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

    public function update(Pengguna $pengguna, Tag $tag): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function delete(Pengguna $pengguna, Tag $tag): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
