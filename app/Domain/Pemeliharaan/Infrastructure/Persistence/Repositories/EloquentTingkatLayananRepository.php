<?php

declare(strict_types=1);

namespace App\Domain\Pemeliharaan\Infrastructure\Persistence\Repositories;

use App\Domain\Pemeliharaan\Domain\Repositories\TingkatLayananRepository;
use App\Domain\Pemeliharaan\Infrastructure\Persistence\Models\TingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTingkatLayananRepository implements TingkatLayananRepository
{
    public function temukan(string $id): ?TingkatLayanan
    {
        return TingkatLayanan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TingkatLayanan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TingkatLayanan $model): TingkatLayanan
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(TingkatLayanan $model): void
    {
        $model->delete();
    }
}
