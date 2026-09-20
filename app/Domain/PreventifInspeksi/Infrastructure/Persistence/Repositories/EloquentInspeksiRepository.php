<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\InspeksiRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentInspeksiRepository implements InspeksiRepository
{
    public function temukan(string $id): ?Inspeksi
    {
        return Inspeksi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Inspeksi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Inspeksi $model): Inspeksi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Inspeksi $model): void
    {
        $model->delete();
    }
}
