<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Infrastructure\Persistence\Repositories;

use App\Domain\Kepatuhan\Domain\Repositories\IntegrasiEksternalRepository;
use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\IntegrasiEksternal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentIntegrasiEksternalRepository implements IntegrasiEksternalRepository
{
    public function temukan(string $id): ?IntegrasiEksternal
    {
        return IntegrasiEksternal::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return IntegrasiEksternal::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(IntegrasiEksternal $model): IntegrasiEksternal
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(IntegrasiEksternal $model): void
    {
        $model->delete();
    }
}
