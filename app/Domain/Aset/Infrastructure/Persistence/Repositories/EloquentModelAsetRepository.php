<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\ModelAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\ModelAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentModelAsetRepository implements ModelAsetRepository
{
    public function temukan(string $id): ?ModelAset
    {
        return ModelAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return ModelAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(ModelAset $model): ModelAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(ModelAset $model): void
    {
        $model->delete();
    }
}
