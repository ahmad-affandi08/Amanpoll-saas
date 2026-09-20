<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\RiwayatStatusPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\RiwayatStatusPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRiwayatStatusPerintahKerjaRepository implements RiwayatStatusPerintahKerjaRepository
{
    public function temukan(string $id): ?RiwayatStatusPerintahKerja
    {
        return RiwayatStatusPerintahKerja::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RiwayatStatusPerintahKerja::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(RiwayatStatusPerintahKerja $model): RiwayatStatusPerintahKerja
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(RiwayatStatusPerintahKerja $model): void
    {
        $model->delete();
    }
}
