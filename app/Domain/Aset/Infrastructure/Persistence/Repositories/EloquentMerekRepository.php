<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\MerekRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentMerekRepository implements MerekRepository
{
    public function temukan(string $id): ?Merek
    {
        return Merek::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Merek::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Merek $model): Merek
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Merek $model): void
    {
        $model->delete();
    }
}
