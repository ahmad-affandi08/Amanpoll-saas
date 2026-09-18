<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPesananPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailPesananPembelianRepository implements DetailPesananPembelianRepository
{
    public function temukan(string $id): ?DetailPesananPembelian
    {
        return DetailPesananPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailPesananPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailPesananPembelian $model): DetailPesananPembelian
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(DetailPesananPembelian $model): void
    {
        $model->delete();
    }
}
