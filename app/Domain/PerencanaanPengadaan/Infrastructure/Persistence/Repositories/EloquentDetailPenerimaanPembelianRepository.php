<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenerimaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailPenerimaanPembelianRepository implements DetailPenerimaanPembelianRepository
{
    public function temukan(string $id): ?DetailPenerimaanPembelian
    {
        return DetailPenerimaanPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailPenerimaanPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailPenerimaanPembelian $model): DetailPenerimaanPembelian
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(DetailPenerimaanPembelian $model): void
    {
        $model->delete();
    }
}
