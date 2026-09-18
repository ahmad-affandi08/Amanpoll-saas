<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kalibrasi\Domain\Repositories\TitikUkurKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTitikUkurKalibrasiRepository implements TitikUkurKalibrasiRepository
{
    public function temukan(string $id): ?TitikUkurKalibrasi
    {
        return TitikUkurKalibrasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TitikUkurKalibrasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TitikUkurKalibrasi $model): TitikUkurKalibrasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(TitikUkurKalibrasi $model): void
    {
        $model->delete();
    }
}
