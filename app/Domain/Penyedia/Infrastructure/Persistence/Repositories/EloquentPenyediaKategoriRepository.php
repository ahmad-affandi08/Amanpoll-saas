<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Repositories;

use App\Domain\Penyedia\Domain\Repositories\PenyediaKategoriRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenyediaKategori;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenyediaKategoriRepository implements PenyediaKategoriRepository
{
    public function temukan(string $id): ?PenyediaKategori
    {
        return PenyediaKategori::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenyediaKategori::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PenyediaKategori $model): PenyediaKategori
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PenyediaKategori $model): void
    {
        $model->delete();
    }
}
