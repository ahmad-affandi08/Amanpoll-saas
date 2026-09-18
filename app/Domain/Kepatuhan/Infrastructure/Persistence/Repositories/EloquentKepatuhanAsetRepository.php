<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\KepatuhanAsetRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\KepatuhanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKepatuhanAsetRepository implements KepatuhanAsetRepository
{
    public function temukan(string $id): ?KepatuhanAset
    {
        return KepatuhanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KepatuhanAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KepatuhanAset $model): KepatuhanAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KepatuhanAset $model): void
    {
        $model->delete();
    }
}
