<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\ReservasiSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentReservasiSukuCadangRepository implements ReservasiSukuCadangRepository
{
    public function temukan(string $id): ?ReservasiSukuCadang
    {
        return ReservasiSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return ReservasiSukuCadang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(ReservasiSukuCadang $model): ReservasiSukuCadang
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(ReservasiSukuCadang $model): void
    {
        $model->delete();
    }
}
