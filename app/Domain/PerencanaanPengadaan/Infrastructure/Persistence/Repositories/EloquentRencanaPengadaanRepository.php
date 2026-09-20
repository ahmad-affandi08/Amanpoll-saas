<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\RencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\RencanaPengadaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRencanaPengadaanRepository implements RencanaPengadaanRepository
{
    public function temukan(string $id): ?RencanaPengadaan
    {
        return RencanaPengadaan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RencanaPengadaan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RencanaPengadaan $model): RencanaPengadaan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(RencanaPengadaan $model): void
    {
        $model->delete();
    }
}
