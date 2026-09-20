<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\DetailPenghapusanAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailPenghapusanAsetRepository implements DetailPenghapusanAsetRepository
{
    public function temukan(string $id): ?DetailPenghapusanAset
    {
        return DetailPenghapusanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailPenghapusanAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailPenghapusanAset $model): DetailPenghapusanAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DetailPenghapusanAset $model): void
    {
        $model->delete();
    }
}
