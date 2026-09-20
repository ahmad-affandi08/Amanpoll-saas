<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPermintaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailPermintaanPembelianRepository implements DetailPermintaanPembelianRepository
{
    public function temukan(string $id): ?DetailPermintaanPembelian
    {
        return DetailPermintaanPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailPermintaanPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailPermintaanPembelian $model): DetailPermintaanPembelian
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DetailPermintaanPembelian $model): void
    {
        $model->delete();
    }
}
