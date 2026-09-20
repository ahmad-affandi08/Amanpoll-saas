<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\TemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTemplatDaftarPeriksaRepository implements TemplatDaftarPeriksaRepository
{
    public function temukan(string $id): ?TemplatDaftarPeriksa
    {
        return TemplatDaftarPeriksa::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TemplatDaftarPeriksa::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TemplatDaftarPeriksa $model): TemplatDaftarPeriksa
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(TemplatDaftarPeriksa $model): void
    {
        $model->delete();
    }
}
