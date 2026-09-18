<?php

declare(strict_types=1);

namespace App\Domain\Aset\Infrastructure\Persistence\Repositories;

use App\Domain\Aset\Domain\Repositories\RiwayatPenanggungJawabAsetRepository;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatPenanggungJawabAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentRiwayatPenanggungJawabAsetRepository implements RiwayatPenanggungJawabAsetRepository
{
    public function temukan(string $id): ?RiwayatPenanggungJawabAset
    {
        return RiwayatPenanggungJawabAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return RiwayatPenanggungJawabAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(RiwayatPenanggungJawabAset $model): RiwayatPenanggungJawabAset
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(RiwayatPenanggungJawabAset $model): void
    {
        $model->delete();
    }
}
