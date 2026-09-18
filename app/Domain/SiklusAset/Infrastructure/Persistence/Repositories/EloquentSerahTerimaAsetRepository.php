<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\SerahTerimaAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentSerahTerimaAsetRepository implements SerahTerimaAsetRepository
{
    public function temukan(string $id): ?SerahTerimaAset
    {
        return SerahTerimaAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return SerahTerimaAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(SerahTerimaAset $model): SerahTerimaAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(SerahTerimaAset $model): void
    {
        $model->delete();
    }
}
