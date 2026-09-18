<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\PersyaratanKepatuhanRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PersyaratanKepatuhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPersyaratanKepatuhanRepository implements PersyaratanKepatuhanRepository
{
    public function temukan(string $id): ?PersyaratanKepatuhan
    {
        return PersyaratanKepatuhan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PersyaratanKepatuhan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PersyaratanKepatuhan $model): PersyaratanKepatuhan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PersyaratanKepatuhan $model): void
    {
        $model->delete();
    }
}
