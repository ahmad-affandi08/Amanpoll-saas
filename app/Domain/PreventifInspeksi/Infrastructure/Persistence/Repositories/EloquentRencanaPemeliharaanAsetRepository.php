<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\RencanaPemeliharaanAsetRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRencanaPemeliharaanAsetRepository implements RencanaPemeliharaanAsetRepository
{
    public function temukan(string $id): ?RencanaPemeliharaanAset
    {
        return RencanaPemeliharaanAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RencanaPemeliharaanAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RencanaPemeliharaanAset $model): RencanaPemeliharaanAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(RencanaPemeliharaanAset $model): void
    {
        $model->delete();
    }
}
