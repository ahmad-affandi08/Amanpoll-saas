<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\PemakaianSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\PemakaianSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPemakaianSukuCadangRepository implements PemakaianSukuCadangRepository
{
    public function temukan(string $id): ?PemakaianSukuCadang
    {
        return PemakaianSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PemakaianSukuCadang::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PemakaianSukuCadang $model): PemakaianSukuCadang
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PemakaianSukuCadang $model): void
    {
        $model->delete();
    }
}
