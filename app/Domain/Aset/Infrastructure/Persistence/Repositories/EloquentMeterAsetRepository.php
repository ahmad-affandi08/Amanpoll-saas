<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\MeterAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentMeterAsetRepository implements MeterAsetRepository
{
    public function temukan(string $id): ?MeterAset
    {
        return MeterAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return MeterAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(MeterAset $model): MeterAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(MeterAset $model): void
    {
        $model->delete();
    }
}
