<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\MutasiStokRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentMutasiStokRepository implements MutasiStokRepository
{
    public function temukan(string $id): ?MutasiStok
    {
        return MutasiStok::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return MutasiStok::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(MutasiStok $model): MutasiStok
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(MutasiStok $model): void
    {
        $model->delete();
    }
}
