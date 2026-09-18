<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\GaransiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\GaransiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentGaransiAsetRepository implements GaransiAsetRepository
{
    public function temukan(string $id): ?GaransiAset
    {
        return GaransiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return GaransiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(GaransiAset $model): GaransiAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(GaransiAset $model): void
    {
        $model->delete();
    }
}
