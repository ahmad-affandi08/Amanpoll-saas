<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Infrastructure\Persistence\Repositories;

use App\Domain\Kontrak\Domain\Repositories\LayananKontrakRepository;
use App\Domain\Kontrak\Infrastructure\Persistence\Models\LayananKontrak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLayananKontrakRepository implements LayananKontrakRepository
{
    public function temukan(string $id): ?LayananKontrak
    {
        return LayananKontrak::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return LayananKontrak::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(LayananKontrak $model): LayananKontrak
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(LayananKontrak $model): void
    {
        $model->delete();
    }
}
