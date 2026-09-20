<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\PenggunaPeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenggunaPeranRepository implements PenggunaPeranRepository
{
    public function temukan(string $id): ?PenggunaPeran
    {
        return PenggunaPeran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenggunaPeran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PenggunaPeran $model): PenggunaPeran
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PenggunaPeran $model): void
    {
        $model->delete();
    }
}
