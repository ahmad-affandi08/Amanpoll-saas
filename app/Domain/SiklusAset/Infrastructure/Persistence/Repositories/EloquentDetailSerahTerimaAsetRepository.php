<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\DetailSerahTerimaAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailSerahTerimaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailSerahTerimaAsetRepository implements DetailSerahTerimaAsetRepository
{
    public function temukan(string $id): ?DetailSerahTerimaAset
    {
        return DetailSerahTerimaAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailSerahTerimaAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailSerahTerimaAset $model): DetailSerahTerimaAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DetailSerahTerimaAset $model): void
    {
        $model->delete();
    }
}
