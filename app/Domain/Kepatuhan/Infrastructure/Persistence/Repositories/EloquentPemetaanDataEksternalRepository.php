<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\PemetaanDataEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\PemetaanDataEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPemetaanDataEksternalRepository implements PemetaanDataEksternalRepository
{
    public function temukan(string $id): ?PemetaanDataEksternal
    {
        return PemetaanDataEksternal::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PemetaanDataEksternal::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PemetaanDataEksternal $model): PemetaanDataEksternal
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PemetaanDataEksternal $model): void
    {
        $model->delete();
    }
}
