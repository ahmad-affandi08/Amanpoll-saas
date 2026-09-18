<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Infrastructure\Persistence\Repositories;

use App\Domain\Kolaborasi\Domain\Repositories\BerkasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentBerkasRepository implements BerkasRepository
{
    public function temukan(string $id): ?Berkas
    {
        return Berkas::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Berkas::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Berkas $model): Berkas
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Berkas $model): void
    {
        $model->delete();
    }
}
