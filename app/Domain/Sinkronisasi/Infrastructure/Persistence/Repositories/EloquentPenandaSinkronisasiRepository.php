<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Infrastructure\Persistence\Repositories;

use App\Domain\Sinkronisasi\Domain\Repositories\PenandaSinkronisasiRepository;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\PenandaSinkronisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenandaSinkronisasiRepository implements PenandaSinkronisasiRepository
{
    public function temukan(string $id): ?PenandaSinkronisasi
    {
        return PenandaSinkronisasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenandaSinkronisasi::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PenandaSinkronisasi $model): PenandaSinkronisasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PenandaSinkronisasi $model): void
    {
        $model->delete();
    }
}
