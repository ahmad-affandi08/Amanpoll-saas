<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\DetailMutasiAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailMutasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailMutasiAsetRepository implements DetailMutasiAsetRepository
{
    public function temukan(string $id): ?DetailMutasiAset
    {
        return DetailMutasiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailMutasiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailMutasiAset $model): DetailMutasiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DetailMutasiAset $model): void
    {
        $model->delete();
    }
}
