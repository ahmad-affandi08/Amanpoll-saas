<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\PeranIzinRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\PeranIzin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPeranIzinRepository implements PeranIzinRepository
{
    public function temukan(string $id): ?PeranIzin
    {
        return PeranIzin::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PeranIzin::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PeranIzin $model): PeranIzin
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PeranIzin $model): void
    {
        $model->delete();
    }
}
