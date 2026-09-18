<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\DetailPenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\DetailPenawaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailPenawaranPenyediaRepository implements DetailPenawaranPenyediaRepository
{
    public function temukan(string $id): ?DetailPenawaranPenyedia
    {
        return DetailPenawaranPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailPenawaranPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailPenawaranPenyedia $model): DetailPenawaranPenyedia
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(DetailPenawaranPenyedia $model): void
    {
        $model->delete();
    }
}
