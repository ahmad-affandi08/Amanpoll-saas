<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\LokasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLokasiRepository implements LokasiRepository
{
    public function temukan(string $id): ?Lokasi
    {
        return Lokasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Lokasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Lokasi $model): Lokasi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Lokasi $model): void
    {
        $model->delete();
    }
}
