<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\FiturPaketRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\FiturPaket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentFiturPaketRepository implements FiturPaketRepository
{
    public function temukan(string $id): ?FiturPaket
    {
        return FiturPaket::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return FiturPaket::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(FiturPaket $model): FiturPaket
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(FiturPaket $model): void
    {
        $model->delete();
    }
}
