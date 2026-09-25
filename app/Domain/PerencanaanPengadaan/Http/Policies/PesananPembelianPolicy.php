<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\PerencanaanPengadaan\Domain\Enums\StatusPesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * PO dikelola bagian pengadaan. Petugas gudang hanya membuka PO yang sudah dikirim ke
 * penyedia, karena di halaman itulah barang yang datang dicatat; draf dan PO yang masih
 * menunggu persetujuan bukan urusannya.
 */
final class PesananPembelianPolicy
{
    private const STATUS_TERBUKA_UNTUK_GUDANG = [
        StatusPesananPembelian::Dikirim,
        StatusPesananPembelian::DiterimaSebagian,
        StatusPesananPembelian::DiterimaPenuh,
    ];

    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->pengadaan($pengguna);
    }

    public function view(Pengguna $pengguna, PesananPembelian $pesanan): bool
    {
        if ($this->pengadaan($pengguna)) {
            return true;
        }

        $terbuka = array_map(fn (StatusPesananPembelian $status): string => $status->value, self::STATUS_TERBUKA_UNTUK_GUDANG);

        return in_array($pesanan->Status, $terbuka, true) && $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->pengadaan($pengguna);
    }

    public function update(Pengguna $pengguna, PesananPembelian $pesanan): bool
    {
        return $this->pengadaan($pengguna);
    }

    public function delete(Pengguna $pengguna, PesananPembelian $pesanan): bool
    {
        return $this->pengadaan($pengguna);
    }

    private function pengadaan(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }
}
