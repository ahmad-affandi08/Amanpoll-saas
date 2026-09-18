<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kalibrasi\Domain\Repositories\JenisKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentJenisKalibrasiRepository implements JenisKalibrasiRepository
{
    public function temukan(string $id): ?JenisKalibrasi
    {
        return JenisKalibrasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return JenisKalibrasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(JenisKalibrasi $model): JenisKalibrasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(JenisKalibrasi $model): void
    {
        $model->delete();
    }
}
