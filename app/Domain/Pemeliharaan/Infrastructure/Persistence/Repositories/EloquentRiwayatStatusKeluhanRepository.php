<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\RiwayatStatusKeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusKeluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRiwayatStatusKeluhanRepository implements RiwayatStatusKeluhanRepository
{
    public function temukan(string $id): ?RiwayatStatusKeluhan
    {
        return RiwayatStatusKeluhan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RiwayatStatusKeluhan::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(RiwayatStatusKeluhan $model): RiwayatStatusKeluhan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(RiwayatStatusKeluhan $model): void
    {
        $model->delete();
    }
}
