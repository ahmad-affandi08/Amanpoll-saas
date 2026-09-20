<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Repositories;

use App\Domain\Pelaporan\Domain\Repositories\DasborTersimpanRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\DasborTersimpan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDasborTersimpanRepository implements DasborTersimpanRepository
{
    public function temukan(string $id): ?DasborTersimpan
    {
        return DasborTersimpan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DasborTersimpan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DasborTersimpan $model): DasborTersimpan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DasborTersimpan $model): void
    {
        $model->delete();
    }
}
