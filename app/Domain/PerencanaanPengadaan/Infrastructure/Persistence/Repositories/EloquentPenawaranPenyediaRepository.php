<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PenawaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PenawaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenawaranPenyediaRepository implements PenawaranPenyediaRepository
{
    public function temukan(string $id): ?PenawaranPenyedia
    {
        return PenawaranPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PenawaranPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PenawaranPenyedia $model): PenawaranPenyedia
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PenawaranPenyedia $model): void
    {
        $model->delete();
    }
}
