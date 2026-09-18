<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Infrastructure\Persistence\Repositories;

use App\Domain\Persetujuan\Domain\Repositories\PermintaanPersetujuanRepository;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPermintaanPersetujuanRepository implements PermintaanPersetujuanRepository
{
    public function temukan(string $id): ?PermintaanPersetujuan
    {
        return PermintaanPersetujuan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PermintaanPersetujuan::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PermintaanPersetujuan $model): PermintaanPersetujuan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PermintaanPersetujuan $model): void
    {
        $model->delete();
    }
}
