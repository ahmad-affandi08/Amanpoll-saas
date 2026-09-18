<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\PembacaanMeterAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\PembacaanMeterAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPembacaanMeterAsetRepository implements PembacaanMeterAsetRepository
{
    public function temukan(string $id): ?PembacaanMeterAset
    {
        return PembacaanMeterAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PembacaanMeterAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PembacaanMeterAset $model): PembacaanMeterAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PembacaanMeterAset $model): void
    {
        $model->delete();
    }
}
