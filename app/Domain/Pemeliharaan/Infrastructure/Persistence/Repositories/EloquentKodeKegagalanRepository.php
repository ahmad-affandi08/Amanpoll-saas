<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\KodeKegagalanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KodeKegagalan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKodeKegagalanRepository implements KodeKegagalanRepository
{
    public function temukan(string $id): ?KodeKegagalan
    {
        return KodeKegagalan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KodeKegagalan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KodeKegagalan $model): KodeKegagalan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KodeKegagalan $model): void
    {
        $model->delete();
    }
}
