<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\PengirimanPanggilanBalikWebRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPengirimanPanggilanBalikWebRepository implements PengirimanPanggilanBalikWebRepository
{
    public function temukan(string $id): ?PengirimanPanggilanBalikWeb
    {
        return PengirimanPanggilanBalikWeb::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PengirimanPanggilanBalikWeb::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PengirimanPanggilanBalikWeb $model): PengirimanPanggilanBalikWeb
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PengirimanPanggilanBalikWeb $model): void
    {
        $model->delete();
    }
}
