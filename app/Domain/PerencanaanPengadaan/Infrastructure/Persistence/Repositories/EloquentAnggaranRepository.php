<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\AnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAnggaranRepository implements AnggaranRepository
{
    public function temukan(string $id): ?Anggaran
    {
        return Anggaran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Anggaran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Anggaran $model): Anggaran
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Anggaran $model): void
    {
        $model->delete();
    }
}
