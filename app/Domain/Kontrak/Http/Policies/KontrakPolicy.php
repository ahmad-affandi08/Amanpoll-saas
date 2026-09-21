<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KontrakPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->boleh($pengguna);
    }

    public function view(Pengguna $pengguna, Kontrak $kontrak): bool
    {
        return $this->boleh($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->boleh($pengguna);
    }

    public function update(Pengguna $pengguna, Kontrak $kontrak): bool
    {
        return $this->boleh($pengguna);
    }

    public function delete(Pengguna $pengguna, Kontrak $kontrak): bool
    {
        return $this->boleh($pengguna);
    }

    private function boleh(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Kontrak.Kelola');
    }
}
