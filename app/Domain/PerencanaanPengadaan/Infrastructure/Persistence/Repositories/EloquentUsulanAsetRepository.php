<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\UsulanAsetRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentUsulanAsetRepository implements UsulanAsetRepository
{
    public function temukan(string $id): ?UsulanAset
    {
        return UsulanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return UsulanAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(UsulanAset $model): UsulanAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(UsulanAset $model): void
    {
        $model->delete();
    }
}
