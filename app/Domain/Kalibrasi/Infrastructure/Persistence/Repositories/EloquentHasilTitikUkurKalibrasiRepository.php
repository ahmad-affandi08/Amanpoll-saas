<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kalibrasi\Domain\Repositories\HasilTitikUkurKalibrasiRepository;
use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentHasilTitikUkurKalibrasiRepository implements HasilTitikUkurKalibrasiRepository
{
    public function temukan(string $id): ?HasilTitikUkurKalibrasi
    {
        return HasilTitikUkurKalibrasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return HasilTitikUkurKalibrasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(HasilTitikUkurKalibrasi $model): HasilTitikUkurKalibrasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(HasilTitikUkurKalibrasi $model): void
    {
        $model->delete();
    }
}
