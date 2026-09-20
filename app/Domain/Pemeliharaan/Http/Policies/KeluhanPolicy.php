<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pemeliharaan\Domain\Enums\StatusKeluhan;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KeluhanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return true;
    }

    public function view(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        return $keluhan->PelaporId === $pengguna->Id || $this->dapatMengelola($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function ubahStatus(Pengguna $pengguna, Keluhan $keluhan, string $statusTujuan): bool
    {
        if ($this->dapatMengelola($pengguna)) {
            return true;
        }

        return $keluhan->PelaporId === $pengguna->Id
            && $statusTujuan === StatusKeluhan::Dibatalkan->value
            && in_array($keluhan->Status, [StatusKeluhan::Baru->value, StatusKeluhan::Ditinjau->value], true);
    }

    public function ubahPrioritas(Pengguna $pengguna, Keluhan $keluhan): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    private function dapatMengelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Keluhan.Kelola');
    }
}
