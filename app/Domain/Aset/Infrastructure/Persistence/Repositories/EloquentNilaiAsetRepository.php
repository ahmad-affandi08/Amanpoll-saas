<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\NilaiAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\NilaiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentNilaiAsetRepository implements NilaiAsetRepository
{
    public function temukan(string $id): ?NilaiAset
    {
        return NilaiAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return NilaiAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(NilaiAset $model): NilaiAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(NilaiAset $model): void
    {
        $model->delete();
    }
}
