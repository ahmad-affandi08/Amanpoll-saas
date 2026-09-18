<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\PelaksanaanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\PelaksanaanDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPelaksanaanDaftarPeriksaRepository implements PelaksanaanDaftarPeriksaRepository
{
    public function temukan(string $id): ?PelaksanaanDaftarPeriksa
    {
        return PelaksanaanDaftarPeriksa::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PelaksanaanDaftarPeriksa::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PelaksanaanDaftarPeriksa $model): PelaksanaanDaftarPeriksa
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PelaksanaanDaftarPeriksa $model): void
    {
        $model->delete();
    }
}
