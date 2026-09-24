<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Http\Policies;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/** Berkas sendiri tidak punya pemilik tetap. */
final class BerkasPolicy
{
    public function __construct(private readonly RegistriEntitas $registriEntitas) {}

    public function create(Pengguna $pengguna): bool
    {
        return true;
    }

    public function view(Pengguna $pengguna, Berkas $berkas): bool
    {
        if ($berkas->DiunggahOleh === $pengguna->Id) {
            return true;
        }

        return $this->bolehMelaluiLampiran($pengguna, $berkas) || $this->bolehLihatMelaluiLampiran($pengguna, $berkas);
    }

    /**
     * Sengaja tidak memakai `view()`: lampiran yang hanya boleh dilihat (mis. foto
     * Sesudah bagi pelapor) tidak ikut boleh dihapus.
     */
    public function delete(Pengguna $pengguna, Berkas $berkas): bool
    {
        if ($berkas->DiunggahOleh === $pengguna->Id) {
            return true;
        }

        return $this->bolehMelaluiLampiran($pengguna, $berkas);
    }

    /**
     * Berkas terbuka bagi siapa pun yang boleh mengelola salah satu entitas
     * tempatnya dilampirkan: lewat izin Kelola jenisnya, atau lewat policy atas
     * baris itu (mis. teknisi yang ditugaskan pada perintah kerjanya).
     */
    private function bolehMelaluiLampiran(Pengguna $pengguna, Berkas $berkas): bool
    {
        $lampiran = LampiranEntitas::query()
            ->where('BerkasId', $berkas->Id)
            ->distinct()
            ->get(['JenisEntitas', 'EntitasId']);

        foreach ($lampiran as $satu) {
            if ($this->registriEntitas->dikenal($satu->JenisEntitas)
                && $this->registriEntitas->bolehKelolaRekaman($pengguna, $satu->JenisEntitas, (string) $satu->EntitasId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lampiran yang dibuka hanya untuk dilihat, per kategori: kemampuan
     * `kemampuanLihatLampiran` jenis entitasnya diperiksa untuk kategori tiap
     * baris lampiran berkas ini (RegistriEntitas).
     */
    private function bolehLihatMelaluiLampiran(Pengguna $pengguna, Berkas $berkas): bool
    {
        $lampiran = LampiranEntitas::query()
            ->where('BerkasId', $berkas->Id)
            ->distinct()
            ->get(['JenisEntitas', 'EntitasId', 'Kategori']);

        foreach ($lampiran as $satu) {
            if ($this->registriEntitas->dikenal($satu->JenisEntitas)
                && $this->registriEntitas->bolehLihatLampiran($pengguna, $satu->JenisEntitas, (string) $satu->EntitasId, $satu->Kategori)) {
                return true;
            }
        }

        return false;
    }
}
