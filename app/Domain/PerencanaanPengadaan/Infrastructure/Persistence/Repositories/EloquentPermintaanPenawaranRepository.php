<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPenawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPermintaanPenawaranRepository implements PermintaanPenawaranRepository
{
    public function temukan(string $id): ?PermintaanPenawaran
    {
        return PermintaanPenawaran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PermintaanPenawaran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PermintaanPenawaran $model): PermintaanPenawaran
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PermintaanPenawaran $model): void
    {
        $model->delete();
    }
}
