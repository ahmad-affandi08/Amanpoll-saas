<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\TemplatInspeksiRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatInspeksi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTemplatInspeksiRepository implements TemplatInspeksiRepository
{
    public function temukan(string $id): ?TemplatInspeksi
    {
        return TemplatInspeksi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TemplatInspeksi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TemplatInspeksi $model): TemplatInspeksi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(TemplatInspeksi $model): void
    {
        $model->delete();
    }
}
