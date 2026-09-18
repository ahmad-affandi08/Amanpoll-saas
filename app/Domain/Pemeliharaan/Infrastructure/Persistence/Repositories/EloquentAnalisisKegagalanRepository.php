<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\AnalisisKegagalanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AnalisisKegagalan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAnalisisKegagalanRepository implements AnalisisKegagalanRepository
{
    public function temukan(string $id): ?AnalisisKegagalan
    {
        return AnalisisKegagalan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return AnalisisKegagalan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(AnalisisKegagalan $model): AnalisisKegagalan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(AnalisisKegagalan $model): void
    {
        $model->delete();
    }
}
