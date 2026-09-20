<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\EntitasTagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentEntitasTagRepository implements EntitasTagRepository
{
    public function temukan(string $id): ?EntitasTag
    {
        return EntitasTag::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return EntitasTag::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(EntitasTag $model): EntitasTag
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(EntitasTag $model): void
    {
        $model->delete();
    }
}
