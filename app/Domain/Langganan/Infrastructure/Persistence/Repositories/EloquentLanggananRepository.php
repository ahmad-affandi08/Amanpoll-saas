<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\LanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\Langganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLanggananRepository implements LanggananRepository
{
    public function temukan(string $id): ?Langganan
    {
        return Langganan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Langganan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Langganan $model): Langganan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Langganan $model): void
    {
        $model->delete();
    }
}
