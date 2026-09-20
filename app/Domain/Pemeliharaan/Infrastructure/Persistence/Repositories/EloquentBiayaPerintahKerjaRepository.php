<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\BiayaPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\BiayaPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentBiayaPerintahKerjaRepository implements BiayaPerintahKerjaRepository
{
    public function temukan(string $id): ?BiayaPerintahKerja
    {
        return BiayaPerintahKerja::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return BiayaPerintahKerja::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(BiayaPerintahKerja $model): BiayaPerintahKerja
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(BiayaPerintahKerja $model): void
    {
        $model->delete();
    }
}
