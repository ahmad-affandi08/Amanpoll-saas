<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\RencanaPemeliharaanRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRencanaPemeliharaanRepository implements RencanaPemeliharaanRepository
{
    public function temukan(string $id): ?RencanaPemeliharaan
    {
        return RencanaPemeliharaan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RencanaPemeliharaan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RencanaPemeliharaan $model): RencanaPemeliharaan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(RencanaPemeliharaan $model): void
    {
        $model->delete();
    }
}
