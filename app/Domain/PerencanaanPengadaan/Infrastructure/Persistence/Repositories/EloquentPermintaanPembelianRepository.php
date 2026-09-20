<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PermintaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPermintaanPembelianRepository implements PermintaanPembelianRepository
{
    public function temukan(string $id): ?PermintaanPembelian
    {
        return PermintaanPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PermintaanPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PermintaanPembelian $model): PermintaanPembelian
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PermintaanPembelian $model): void
    {
        $model->delete();
    }
}
