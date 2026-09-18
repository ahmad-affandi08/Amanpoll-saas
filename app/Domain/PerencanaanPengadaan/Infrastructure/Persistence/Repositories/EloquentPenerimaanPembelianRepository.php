<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenerimaanPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenerimaanPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenerimaanPembelianRepository implements PenerimaanPembelianRepository
{
    public function temukan(string $id): ?PenerimaanPembelian
    {
        return PenerimaanPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenerimaanPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PenerimaanPembelian $model): PenerimaanPembelian
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PenerimaanPembelian $model): void
    {
        $model->delete();
    }
}
