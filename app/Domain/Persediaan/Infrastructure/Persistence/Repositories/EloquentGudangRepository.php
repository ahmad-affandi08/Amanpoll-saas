<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\GudangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\Gudang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentGudangRepository implements GudangRepository
{
    public function temukan(string $id): ?Gudang
    {
        return Gudang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Gudang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Gudang $model): Gudang
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(Gudang $model): void
    {
        $model->delete();
    }
}
