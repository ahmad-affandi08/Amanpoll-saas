<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Repositories;

use App\Domain\Kontrak\Domain\Repositories\KontrakRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKontrakRepository implements KontrakRepository
{
    public function temukan(string $id): ?Kontrak
    {
        return Kontrak::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Kontrak::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Kontrak $model): Kontrak
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Kontrak $model): void
    {
        $model->delete();
    }
}
