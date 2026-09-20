<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Repositories;

use App\Domain\Penyedia\Domain\Repositories\KategoriPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KategoriPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKategoriPenyediaRepository implements KategoriPenyediaRepository
{
    public function temukan(string $id): ?KategoriPenyedia
    {
        return KategoriPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KategoriPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KategoriPenyedia $model): KategoriPenyedia
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KategoriPenyedia $model): void
    {
        $model->delete();
    }
}
