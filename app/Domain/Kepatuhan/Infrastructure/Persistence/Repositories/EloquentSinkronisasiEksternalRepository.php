<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\SinkronisasiEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SinkronisasiEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentSinkronisasiEksternalRepository implements SinkronisasiEksternalRepository
{
    public function temukan(string $id): ?SinkronisasiEksternal
    {
        return SinkronisasiEksternal::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return SinkronisasiEksternal::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(SinkronisasiEksternal $model): SinkronisasiEksternal
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(SinkronisasiEksternal $model): void
    {
        $model->delete();
    }
}
