<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PosAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPosAnggaranRepository implements PosAnggaranRepository
{
    public function temukan(string $id): ?PosAnggaran
    {
        return PosAnggaran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PosAnggaran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PosAnggaran $model): PosAnggaran
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PosAnggaran $model): void
    {
        $model->delete();
    }
}
