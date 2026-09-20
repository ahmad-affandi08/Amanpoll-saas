<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\RiwayatLokasiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRiwayatLokasiAsetRepository implements RiwayatLokasiAsetRepository
{
    public function temukan(string $id): ?RiwayatLokasiAset
    {
        return RiwayatLokasiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RiwayatLokasiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RiwayatLokasiAset $model): RiwayatLokasiAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(RiwayatLokasiAset $model): void
    {
        $model->delete();
    }
}
