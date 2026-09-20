<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\PerintahKerjaAsetRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerjaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPerintahKerjaAsetRepository implements PerintahKerjaAsetRepository
{
    public function temukan(string $id): ?PerintahKerjaAset
    {
        return PerintahKerjaAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PerintahKerjaAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PerintahKerjaAset $model): PerintahKerjaAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PerintahKerjaAset $model): void
    {
        $model->delete();
    }
}
