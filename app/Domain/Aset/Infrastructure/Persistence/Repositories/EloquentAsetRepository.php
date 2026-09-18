<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\AsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAsetRepository implements AsetRepository
{
    public function temukan(string $id): ?Aset
    {
        return Aset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Aset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Aset $model): Aset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Aset $model): void
    {
        $model->delete();
    }
}
