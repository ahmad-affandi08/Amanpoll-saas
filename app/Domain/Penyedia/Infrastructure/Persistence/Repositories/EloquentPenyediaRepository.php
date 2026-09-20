<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Repositories;

use App\Domain\Penyedia\Domain\Repositories\PenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\Penyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenyediaRepository implements PenyediaRepository
{
    public function temukan(string $id): ?Penyedia
    {
        return Penyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Penyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Penyedia $model): Penyedia
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Penyedia $model): void
    {
        $model->delete();
    }
}
