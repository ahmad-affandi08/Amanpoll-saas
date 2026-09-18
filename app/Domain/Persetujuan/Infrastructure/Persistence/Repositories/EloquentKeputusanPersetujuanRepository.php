<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Repositories;

use App\Domain\Persetujuan\Domain\Repositories\KeputusanPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKeputusanPersetujuanRepository implements KeputusanPersetujuanRepository
{
    public function temukan(string $id): ?KeputusanPersetujuan
    {
        return KeputusanPersetujuan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KeputusanPersetujuan::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(KeputusanPersetujuan $model): KeputusanPersetujuan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KeputusanPersetujuan $model): void
    {
        $model->delete();
    }
}
