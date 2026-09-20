<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\DefinisiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\DefinisiKolomKustom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDefinisiKolomKustomRepository implements DefinisiKolomKustomRepository
{
    public function temukan(string $id): ?DefinisiKolomKustom
    {
        return DefinisiKolomKustom::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DefinisiKolomKustom::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DefinisiKolomKustom $model): DefinisiKolomKustom
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(DefinisiKolomKustom $model): void
    {
        $model->delete();
    }
}
