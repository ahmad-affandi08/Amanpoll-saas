<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\PerangkatPenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\PerangkatPengguna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPerangkatPenggunaRepository implements PerangkatPenggunaRepository
{
    public function temukan(string $id): ?PerangkatPengguna
    {
        return PerangkatPengguna::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PerangkatPengguna::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PerangkatPengguna $model): PerangkatPengguna
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PerangkatPengguna $model): void
    {
        $model->delete();
    }
}
