<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Repositories;

use App\Domain\Pelaporan\Domain\Repositories\LaporanTersimpanRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\LaporanTersimpan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLaporanTersimpanRepository implements LaporanTersimpanRepository
{
    public function temukan(string $id): ?LaporanTersimpan
    {
        return LaporanTersimpan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return LaporanTersimpan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(LaporanTersimpan $model): LaporanTersimpan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(LaporanTersimpan $model): void
    {
        $model->delete();
    }
}
