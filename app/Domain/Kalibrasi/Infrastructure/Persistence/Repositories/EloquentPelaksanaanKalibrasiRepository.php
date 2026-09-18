<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kalibrasi\Domain\Repositories\PelaksanaanKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPelaksanaanKalibrasiRepository implements PelaksanaanKalibrasiRepository
{
    public function temukan(string $id): ?PelaksanaanKalibrasi
    {
        return PelaksanaanKalibrasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PelaksanaanKalibrasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PelaksanaanKalibrasi $model): PelaksanaanKalibrasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PelaksanaanKalibrasi $model): void
    {
        $model->delete();
    }
}
