<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAksesRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCatatanAksesRepository implements CatatanAksesRepository
{
    public function temukan(string $id): ?CatatanAkses
    {
        return CatatanAkses::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return CatatanAkses::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(CatatanAkses $model): CatatanAkses
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(CatatanAkses $model): void
    {
        $model->delete();
    }
}
