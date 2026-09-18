<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\KotakKeluarPeristiwaRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KotakKeluarPeristiwa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKotakKeluarPeristiwaRepository implements KotakKeluarPeristiwaRepository
{
    public function temukan(string $id): ?KotakKeluarPeristiwa
    {
        return KotakKeluarPeristiwa::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KotakKeluarPeristiwa::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KotakKeluarPeristiwa $model): KotakKeluarPeristiwa
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KotakKeluarPeristiwa $model): void
    {
        $model->delete();
    }
}
