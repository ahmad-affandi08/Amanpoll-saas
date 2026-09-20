<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Persistence\Repositories;

use App\Domain\Sinkronisasi\Domain\Repositories\AntrianSinkronisasiRepository;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAntrianSinkronisasiRepository implements AntrianSinkronisasiRepository
{
    public function temukan(string $id): ?AntrianSinkronisasi
    {
        return AntrianSinkronisasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return AntrianSinkronisasi::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(AntrianSinkronisasi $model): AntrianSinkronisasi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(AntrianSinkronisasi $model): void
    {
        $model->delete();
    }
}
