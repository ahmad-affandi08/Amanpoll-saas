<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Policies;

use App\Core\Entitas\RegistriEntitas;
use App\Core\Izin\PemeriksaIzin;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Berkas sendiri tidak punya pemilik tetap -- otorisasi mengikuti entitas
 * induk tempat ia dilampirkan (izin "kelola" milik JenisEntitas-nya), supaya
 * konsisten dengan aturan LampiranEntitas dan langsung berlaku untuk entitas
 * baru (mis. Aset) tanpa perlu policy baru.
 */
final class BerkasPolicy
{
    public function __construct(
        private readonly RegistriEntitas $registriEntitas,
        private readonly PemeriksaIzin $pemeriksaIzin,
    ) {}

    public function create(Pengguna $pengguna): bool
    {
        return true;
    }

    public function view(Pengguna $pengguna, Berkas $berkas): bool
    {
        if ($berkas->DiunggahOleh === $pengguna->Id) {
            return true;
        }

        return $this->bolehMelaluiLampiran($pengguna, $berkas);
    }

    public function delete(Pengguna $pengguna, Berkas $berkas): bool
    {
        return $this->view($pengguna, $berkas);
    }

    private function bolehMelaluiLampiran(Pengguna $pengguna, Berkas $berkas): bool
    {
        $jenisTerlampir = LampiranEntitas::query()
            ->where('BerkasId', $berkas->Id)
            ->distinct()
            ->pluck('JenisEntitas');

        foreach ($jenisTerlampir as $jenis) {
            if ($this->registriEntitas->dikenal($jenis)
                && $this->pemeriksaIzin->boleh($pengguna->Id, $this->registriEntitas->izinKelolaUntuk($jenis))) {
                return true;
            }
        }

        return false;
    }
}
