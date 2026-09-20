<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\PanggilanBalikWebRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPanggilanBalikWebRepository implements PanggilanBalikWebRepository
{
    public function temukan(string $id): ?PanggilanBalikWeb
    {
        return PanggilanBalikWeb::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PanggilanBalikWeb::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PanggilanBalikWeb $model): PanggilanBalikWeb
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PanggilanBalikWeb $model): void
    {
        $model->delete();
    }
}
