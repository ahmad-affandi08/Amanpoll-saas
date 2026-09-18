<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\DetailMutasiStokRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentDetailMutasiStokRepository implements DetailMutasiStokRepository
{
    public function temukan(string $id): ?DetailMutasiStok
    {
        return DetailMutasiStok::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return DetailMutasiStok::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(DetailMutasiStok $model): DetailMutasiStok
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(DetailMutasiStok $model): void
    {
        $model->delete();
    }
}
