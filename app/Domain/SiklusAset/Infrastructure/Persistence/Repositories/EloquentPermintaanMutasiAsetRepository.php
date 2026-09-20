<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\PermintaanMutasiAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPermintaanMutasiAsetRepository implements PermintaanMutasiAsetRepository
{
    public function temukan(string $id): ?PermintaanMutasiAset
    {
        return PermintaanMutasiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PermintaanMutasiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PermintaanMutasiAset $model): PermintaanMutasiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PermintaanMutasiAset $model): void
    {
        $model->delete();
    }
}
