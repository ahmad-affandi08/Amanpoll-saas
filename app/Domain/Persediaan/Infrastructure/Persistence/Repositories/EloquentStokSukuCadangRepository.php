<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\StokSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\StokSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentStokSukuCadangRepository implements StokSukuCadangRepository
{
    public function temukan(string $id): ?StokSukuCadang
    {
        return StokSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return StokSukuCadang::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(StokSukuCadang $model): StokSukuCadang
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(StokSukuCadang $model): void
    {
        $model->delete();
    }
}
