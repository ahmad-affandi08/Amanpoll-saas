<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Persistence\Repositories;

use App\Domain\SiklusAset\Domain\Repositories\PengajuanPenghapusanAsetRepository;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPengajuanPenghapusanAsetRepository implements PengajuanPenghapusanAsetRepository
{
    public function temukan(string $id): ?PengajuanPenghapusanAset
    {
        return PengajuanPenghapusanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PengajuanPenghapusanAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PengajuanPenghapusanAset $model): PengajuanPenghapusanAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PengajuanPenghapusanAset $model): void
    {
        $model->delete();
    }
}
