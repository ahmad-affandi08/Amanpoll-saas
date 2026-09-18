<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\JadwalPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentJadwalPemeliharaanRepository implements JadwalPemeliharaanRepository
{
    public function temukan(string $id): ?JadwalPemeliharaan
    {
        return JadwalPemeliharaan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return JadwalPemeliharaan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(JadwalPemeliharaan $model): JadwalPemeliharaan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(JadwalPemeliharaan $model): void
    {
        $model->delete();
    }
}
