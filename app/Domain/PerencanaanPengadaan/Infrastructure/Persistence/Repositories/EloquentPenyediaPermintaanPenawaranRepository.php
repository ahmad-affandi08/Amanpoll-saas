<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenyediaPermintaanPenawaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenyediaPermintaanPenawaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenyediaPermintaanPenawaranRepository implements PenyediaPermintaanPenawaranRepository
{
    public function temukan(string $id): ?PenyediaPermintaanPenawaran
    {
        return PenyediaPermintaanPenawaran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenyediaPermintaanPenawaran::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PenyediaPermintaanPenawaran $model): PenyediaPermintaanPenawaran
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PenyediaPermintaanPenawaran $model): void
    {
        $model->delete();
    }
}
