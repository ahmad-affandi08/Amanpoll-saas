<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Infrastructure\Persistence\Repositories;

use App\Domain\Penyedia\Domain\Repositories\PenilaianPenyediaRepository;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\PenilaianPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenilaianPenyediaRepository implements PenilaianPenyediaRepository
{
    public function temukan(string $id): ?PenilaianPenyedia
    {
        return PenilaianPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenilaianPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PenilaianPenyedia $model): PenilaianPenyedia
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PenilaianPenyedia $model): void
    {
        $model->delete();
    }
}
