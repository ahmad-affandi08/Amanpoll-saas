<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\AturanTingkatLayananRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\AturanTingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentAturanTingkatLayananRepository implements AturanTingkatLayananRepository
{
    public function temukan(string $id): ?AturanTingkatLayanan
    {
        return AturanTingkatLayanan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return AturanTingkatLayanan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(AturanTingkatLayanan $model): AturanTingkatLayanan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(AturanTingkatLayanan $model): void
    {
        $model->delete();
    }
}
