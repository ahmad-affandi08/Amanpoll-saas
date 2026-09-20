<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\KategoriKeluhanRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\KategoriKeluhan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKategoriKeluhanRepository implements KategoriKeluhanRepository
{
    public function temukan(string $id): ?KategoriKeluhan
    {
        return KategoriKeluhan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KategoriKeluhan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KategoriKeluhan $model): KategoriKeluhan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KategoriKeluhan $model): void
    {
        $model->delete();
    }
}
