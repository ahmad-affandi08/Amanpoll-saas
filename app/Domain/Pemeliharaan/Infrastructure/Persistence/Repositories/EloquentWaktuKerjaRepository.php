<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\WaktuKerjaRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\WaktuKerja;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentWaktuKerjaRepository implements WaktuKerjaRepository
{
    public function temukan(string $id): ?WaktuKerja
    {
        return WaktuKerja::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return WaktuKerja::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(WaktuKerja $model): WaktuKerja
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(WaktuKerja $model): void
    {
        $model->delete();
    }
}
