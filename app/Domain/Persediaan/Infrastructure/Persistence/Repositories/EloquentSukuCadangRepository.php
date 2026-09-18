<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\SukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\SukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentSukuCadangRepository implements SukuCadangRepository
{
    public function temukan(string $id): ?SukuCadang
    {
        return SukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return SukuCadang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(SukuCadang $model): SukuCadang
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(SukuCadang $model): void
    {
        $model->delete();
    }
}
