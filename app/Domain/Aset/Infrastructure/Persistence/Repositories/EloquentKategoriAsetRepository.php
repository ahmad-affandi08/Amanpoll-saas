<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\KategoriAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\KategoriAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKategoriAsetRepository implements KategoriAsetRepository
{
    public function temukan(string $id): ?KategoriAset
    {
        return KategoriAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KategoriAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KategoriAset $model): KategoriAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KategoriAset $model): void
    {
        $model->delete();
    }
}
