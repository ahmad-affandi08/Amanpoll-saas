<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\KategoriSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KategoriSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKategoriSukuCadangRepository implements KategoriSukuCadangRepository
{
    public function temukan(string $id): ?KategoriSukuCadang
    {
        return KategoriSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KategoriSukuCadang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KategoriSukuCadang $model): KategoriSukuCadang
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KategoriSukuCadang $model): void
    {
        $model->delete();
    }
}
