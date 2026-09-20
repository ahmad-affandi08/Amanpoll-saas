<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\PerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPerintahKerjaRepository implements PerintahKerjaRepository
{
    public function temukan(string $id): ?PerintahKerja
    {
        return PerintahKerja::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PerintahKerja::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PerintahKerja $model): PerintahKerja
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PerintahKerja $model): void
    {
        $model->delete();
    }
}
