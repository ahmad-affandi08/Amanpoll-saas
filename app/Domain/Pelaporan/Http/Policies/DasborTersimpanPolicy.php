<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Http\Policies;

use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

/**
 * Kepemilikan dasbor tersimpan (21.04). Dasbor tanpa pemilik adalah dasbor
 * organisasi: dapat dilihat semua orang, tetapi hanya dapat diubah lewat
 * seeding, bukan oleh pengguna biasa.
 */
final class DasborTersimpanPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, DasborTersimpan $dasbor): bool
    {
        return $pengguna->Status === 'Aktif'
            && ($dasbor->PemilikId === null || $dasbor->PemilikId === $pengguna->Id);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function update(Pengguna $pengguna, DasborTersimpan $dasbor): bool
    {
        return $pengguna->Status === 'Aktif' && $dasbor->PemilikId === $pengguna->Id;
    }

    public function delete(Pengguna $pengguna, DasborTersimpan $dasbor): bool
    {
        return $this->update($pengguna, $dasbor);
    }
}
