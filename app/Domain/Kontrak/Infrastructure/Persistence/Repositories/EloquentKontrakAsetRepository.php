<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Repositories;

use App\Domain\Kontrak\Domain\Repositories\KontrakAsetRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\KontrakAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKontrakAsetRepository implements KontrakAsetRepository
{
    public function temukan(string $id): ?KontrakAset
    {
        return KontrakAset::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KontrakAset::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KontrakAset $model): KontrakAset
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KontrakAset $model): void
    {
        $model->delete();
    }
}
