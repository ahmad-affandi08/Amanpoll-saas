<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\ButirTemplatDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentButirTemplatDaftarPeriksaRepository implements ButirTemplatDaftarPeriksaRepository
{
    public function temukan(string $id): ?ButirTemplatDaftarPeriksa
    {
        return ButirTemplatDaftarPeriksa::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return ButirTemplatDaftarPeriksa::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(ButirTemplatDaftarPeriksa $model): ButirTemplatDaftarPeriksa
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(ButirTemplatDaftarPeriksa $model): void
    {
        $model->delete();
    }
}
