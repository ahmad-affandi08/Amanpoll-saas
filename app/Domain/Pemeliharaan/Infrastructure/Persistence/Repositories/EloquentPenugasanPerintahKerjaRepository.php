<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\PenugasanPerintahKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\PenugasanPerintahKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenugasanPerintahKerjaRepository implements PenugasanPerintahKerjaRepository
{
    public function temukan(string $id): ?PenugasanPerintahKerja
    {
        return PenugasanPerintahKerja::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenugasanPerintahKerja::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PenugasanPerintahKerja $model): PenugasanPerintahKerja
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PenugasanPerintahKerja $model): void
    {
        $model->delete();
    }
}
