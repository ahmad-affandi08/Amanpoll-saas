<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kalibrasi\Domain\Repositories\RencanaKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\RencanaKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRencanaKalibrasiRepository implements RencanaKalibrasiRepository
{
    public function temukan(string $id): ?RencanaKalibrasi
    {
        return RencanaKalibrasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RencanaKalibrasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RencanaKalibrasi $model): RencanaKalibrasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(RencanaKalibrasi $model): void
    {
        $model->delete();
    }
}
