<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\RelasiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\RelasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRelasiAsetRepository implements RelasiAsetRepository
{
    public function temukan(string $id): ?RelasiAset
    {
        return RelasiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RelasiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RelasiAset $model): RelasiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(RelasiAset $model): void
    {
        $model->delete();
    }
}
