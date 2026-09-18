<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\KeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\Keluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKeluhanRepository implements KeluhanRepository
{
    public function temukan(string $id): ?Keluhan
    {
        return Keluhan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Keluhan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Keluhan $model): Keluhan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Keluhan $model): void
    {
        $model->delete();
    }
}
