<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\IzinRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Izin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentIzinRepository implements IzinRepository
{
    public function temukan(string $id): ?Izin
    {
        return Izin::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Izin::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Izin $model): Izin
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Izin $model): void
    {
        $model->delete();
    }
}
