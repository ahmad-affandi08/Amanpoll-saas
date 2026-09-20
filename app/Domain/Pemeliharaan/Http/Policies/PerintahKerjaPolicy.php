<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Pemeliharaan\Domain\Enums\StatusPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class PerintahKerjaPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna) || $this->ditugaskan($pengguna, $perintahKerja);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    public function assign(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    public function ubahStatus(Pengguna $pengguna, PerintahKerja $perintahKerja, string $statusTujuan): bool
    {
        if ($this->dapatMengelola($pengguna)) {
            return true;
        }

        return $this->ditugaskan($pengguna, $perintahKerja)
            && in_array($statusTujuan, [
                StatusPerintahKerja::Dikerjakan->value,
                StatusPerintahKerja::MenungguSukuCadang->value,
                StatusPerintahKerja::MenungguPenyedia->value,
                StatusPerintahKerja::Dijeda->value,
                StatusPerintahKerja::MenungguVerifikasi->value,
            ], true);
    }

    public function responsPenugasan(Pengguna $pengguna, PerintahKerja $perintahKerja, PenugasanPerintahKerja $penugasan): bool
    {
        return $penugasan->PerintahKerjaId === $perintahKerja->Id && $penugasan->PenggunaId === $pengguna->Id;
    }

    public function operate(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna) || $this->ditugaskan($pengguna, $perintahKerja);
    }

    public function manageCost(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $this->dapatMengelola($pengguna);
    }

    private function ditugaskan(Pengguna $pengguna, PerintahKerja $perintahKerja): bool
    {
        return $perintahKerja->penugasan()
            ->where('PenggunaId', $pengguna->Id)
            ->whereIn('Status', ['Ditugaskan', 'Diterima'])
            ->exists();
    }

    private function dapatMengelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'PerintahKerja.Kelola');
    }
}
