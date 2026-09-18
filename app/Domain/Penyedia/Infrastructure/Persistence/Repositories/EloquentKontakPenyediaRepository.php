<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Repositories;

use App\Domain\Penyedia\Domain\Repositories\KontakPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKontakPenyediaRepository implements KontakPenyediaRepository
{
    public function temukan(string $id): ?KontakPenyedia
    {
        return KontakPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KontakPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KontakPenyedia $model): KontakPenyedia
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KontakPenyedia $model): void
    {
        $model->delete();
    }
}
