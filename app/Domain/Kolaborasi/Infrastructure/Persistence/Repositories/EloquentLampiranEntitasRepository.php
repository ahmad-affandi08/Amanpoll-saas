<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\LampiranEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLampiranEntitasRepository implements LampiranEntitasRepository
{
    public function temukan(string $id): ?LampiranEntitas
    {
        return LampiranEntitas::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return LampiranEntitas::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(LampiranEntitas $model): LampiranEntitas
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(LampiranEntitas $model): void
    {
        $model->delete();
    }
}
