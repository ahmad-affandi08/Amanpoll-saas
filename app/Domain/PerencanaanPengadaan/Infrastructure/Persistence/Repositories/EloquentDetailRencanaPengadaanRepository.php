<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailRencanaPengadaanRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailRencanaPengadaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailRencanaPengadaanRepository implements DetailRencanaPengadaanRepository
{
    public function temukan(string $id): ?DetailRencanaPengadaan
    {
        return DetailRencanaPengadaan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailRencanaPengadaan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailRencanaPengadaan $model): DetailRencanaPengadaan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(DetailRencanaPengadaan $model): void
    {
        $model->delete();
    }
}
