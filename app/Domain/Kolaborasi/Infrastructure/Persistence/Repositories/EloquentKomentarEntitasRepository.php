<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKomentarEntitasRepository implements KomentarEntitasRepository
{
    public function temukan(string $id): ?KomentarEntitas
    {
        return KomentarEntitas::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KomentarEntitas::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KomentarEntitas $model): KomentarEntitas
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KomentarEntitas $model): void
    {
        $model->delete();
    }
}
