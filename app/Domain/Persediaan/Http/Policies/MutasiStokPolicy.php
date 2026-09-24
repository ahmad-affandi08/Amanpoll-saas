<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persediaan\Application\Services\LingkupGudang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Mutasi mengikuti lingkup gudangnya (PRD 8.21).
 *
 * Melihat cukup salah satu sisi terlihat -- sama dengan daftar mutasi -- supaya
 * gudang IT tetap bisa membaca transfer yang masuk dari gudang lain. Mengubah
 * (detail, posting, batal) menuntut seluruh sisi terlihat, karena memposting
 * transfer menggerakkan stok di kedua gudang.
 */
final class MutasiStokPolicy
{
    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly LingkupGudang $lingkupGudang,
    ) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function view(Pengguna $pengguna, MutasiStok $mutasiStok): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola')
            && ($this->lingkupGudang->terlihat($mutasiStok->GudangAsalId) || $this->lingkupGudang->terlihat($mutasiStok->GudangTujuanId));
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Stok.Kelola');
    }

    public function update(Pengguna $pengguna, MutasiStok $mutasiStok): bool
    {
        if (! $this->izin->boleh($pengguna->Id, 'Stok.Kelola')) {
            return false;
        }

        foreach ([$mutasiStok->GudangAsalId, $mutasiStok->GudangTujuanId] as $gudangId) {
            if ($gudangId !== null && ! $this->lingkupGudang->terlihat($gudangId)) {
                return false;
            }
        }

        return true;
    }
}
