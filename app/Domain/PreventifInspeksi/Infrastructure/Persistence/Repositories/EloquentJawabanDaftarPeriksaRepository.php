<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Infrastructure\Persistence\Repositories;

use App\Domain\PreventifInspeksi\Domain\Repositories\JawabanDaftarPeriksaRepository;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentJawabanDaftarPeriksaRepository implements JawabanDaftarPeriksaRepository
{
    public function temukan(string $id): ?JawabanDaftarPeriksa
    {
        return JawabanDaftarPeriksa::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return JawabanDaftarPeriksa::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(JawabanDaftarPeriksa $model): JawabanDaftarPeriksa
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(JawabanDaftarPeriksa $model): void
    {
        $model->delete();
    }
}
