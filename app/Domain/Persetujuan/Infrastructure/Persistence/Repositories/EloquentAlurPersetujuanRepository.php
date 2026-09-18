<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Repositories;

use App\Domain\Persetujuan\Domain\Repositories\AlurPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAlurPersetujuanRepository implements AlurPersetujuanRepository
{
    public function temukan(string $id): ?AlurPersetujuan
    {
        return AlurPersetujuan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return AlurPersetujuan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(AlurPersetujuan $model): AlurPersetujuan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(AlurPersetujuan $model): void
    {
        $model->delete();
    }
}
