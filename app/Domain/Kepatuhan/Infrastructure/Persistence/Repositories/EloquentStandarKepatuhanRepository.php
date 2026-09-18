<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\StandarKepatuhanRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\StandarKepatuhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentStandarKepatuhanRepository implements StandarKepatuhanRepository
{
    public function temukan(string $id): ?StandarKepatuhan
    {
        return StandarKepatuhan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return StandarKepatuhan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(StandarKepatuhan $model): StandarKepatuhan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(StandarKepatuhan $model): void
    {
        $model->delete();
    }
}
