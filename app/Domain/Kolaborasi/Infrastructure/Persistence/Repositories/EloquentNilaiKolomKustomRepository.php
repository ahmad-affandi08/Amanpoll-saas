<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\NilaiKolomKustomRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\NilaiKolomKustom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentNilaiKolomKustomRepository implements NilaiKolomKustomRepository
{
    public function temukan(string $id): ?NilaiKolomKustom
    {
        return NilaiKolomKustom::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return NilaiKolomKustom::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(NilaiKolomKustom $model): NilaiKolomKustom
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(NilaiKolomKustom $model): void
    {
        $model->delete();
    }
}
