<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Repositories;

use App\Domain\Notifikasi\Domain\Repositories\TemplatNotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTemplatNotifikasiRepository implements TemplatNotifikasiRepository
{
    public function temukan(string $id): ?TemplatNotifikasi
    {
        return TemplatNotifikasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TemplatNotifikasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TemplatNotifikasi $model): TemplatNotifikasi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(TemplatNotifikasi $model): void
    {
        $model->delete();
    }
}
