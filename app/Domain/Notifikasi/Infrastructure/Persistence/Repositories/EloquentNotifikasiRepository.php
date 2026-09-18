<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Repositories;

use App\Domain\Notifikasi\Domain\Repositories\NotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\Notifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentNotifikasiRepository implements NotifikasiRepository
{
    public function temukan(string $id): ?Notifikasi
    {
        return Notifikasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Notifikasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Notifikasi $model): Notifikasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Notifikasi $model): void
    {
        $model->delete();
    }
}
