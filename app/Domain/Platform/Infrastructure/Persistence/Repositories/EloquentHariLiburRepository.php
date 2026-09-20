<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\HariLiburRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\HariLibur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentHariLiburRepository implements HariLiburRepository
{
    public function temukan(string $id): ?HariLibur
    {
        return HariLibur::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return HariLibur::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(HariLibur $model): HariLibur
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(HariLibur $model): void
    {
        $model->delete();
    }
}
