<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\TagihanPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TagihanPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTagihanPenyediaRepository implements TagihanPenyediaRepository
{
    public function temukan(string $id): ?TagihanPenyedia
    {
        return TagihanPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TagihanPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TagihanPenyedia $model): TagihanPenyedia
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(TagihanPenyedia $model): void
    {
        $model->delete();
    }
}
