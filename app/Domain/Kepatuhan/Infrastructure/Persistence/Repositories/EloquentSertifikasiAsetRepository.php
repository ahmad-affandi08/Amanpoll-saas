<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\SertifikasiAsetRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentSertifikasiAsetRepository implements SertifikasiAsetRepository
{
    public function temukan(string $id): ?SertifikasiAset
    {
        return SertifikasiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return SertifikasiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(SertifikasiAset $model): SertifikasiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(SertifikasiAset $model): void
    {
        $model->delete();
    }
}
