<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPeranRepository implements PeranRepository
{
    public function temukan(string $id): ?Peran
    {
        return Peran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Peran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Peran $model): Peran
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Peran $model): void
    {
        $model->delete();
    }
}
