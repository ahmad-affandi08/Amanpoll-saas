<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenilaianUsulanAsetRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenilaianUsulanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenilaianUsulanAsetRepository implements PenilaianUsulanAsetRepository
{
    public function temukan(string $id): ?PenilaianUsulanAset
    {
        return PenilaianUsulanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenilaianUsulanAset::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PenilaianUsulanAset $model): PenilaianUsulanAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PenilaianUsulanAset $model): void
    {
        $model->delete();
    }
}
