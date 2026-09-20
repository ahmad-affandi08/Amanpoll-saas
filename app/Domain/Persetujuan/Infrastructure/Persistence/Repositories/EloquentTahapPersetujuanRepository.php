<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Repositories;

use App\Domain\Persetujuan\Domain\Repositories\TahapPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTahapPersetujuanRepository implements TahapPersetujuanRepository
{
    public function temukan(string $id): ?TahapPersetujuan
    {
        return TahapPersetujuan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TahapPersetujuan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TahapPersetujuan $model): TahapPersetujuan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(TahapPersetujuan $model): void
    {
        $model->delete();
    }
}
