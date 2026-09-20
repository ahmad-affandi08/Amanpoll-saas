<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\WaktuHentiAsetRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuHentiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentWaktuHentiAsetRepository implements WaktuHentiAsetRepository
{
    public function temukan(string $id): ?WaktuHentiAset
    {
        return WaktuHentiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return WaktuHentiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(WaktuHentiAset $model): WaktuHentiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(WaktuHentiAset $model): void
    {
        $model->delete();
    }
}
