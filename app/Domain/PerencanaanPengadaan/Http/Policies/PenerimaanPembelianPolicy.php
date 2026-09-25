<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Barang datang di gudang, jadi petugas gudang (`Stok.Kelola`) ikut mencatat penerimaan
 * di samping bagian pengadaan. Mengubah atau menghapus dokumen tetap urusan pengadaan.
 */
final class PenerimaanPembelianPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->bolehMencatat($pengguna);
    }

    public function view(Pengguna $pengguna, PenerimaanPembelian $penerimaan): bool
    {
        return $this->bolehMencatat($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->bolehMencatat($pengguna);
    }

    public function update(Pengguna $pengguna, PenerimaanPembelian $penerimaan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }

    public function delete(Pengguna $pengguna, PenerimaanPembelian $penerimaan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }

    private function bolehMencatat(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola') || $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }
}
