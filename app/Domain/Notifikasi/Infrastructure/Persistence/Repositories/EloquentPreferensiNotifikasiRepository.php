<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Repositories;

use App\Domain\Notifikasi\Domain\Repositories\PreferensiNotifikasiRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPreferensiNotifikasiRepository implements PreferensiNotifikasiRepository
{
    public function temukan(string $id): ?PreferensiNotifikasi
    {
        return PreferensiNotifikasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PreferensiNotifikasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PreferensiNotifikasi $model): PreferensiNotifikasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PreferensiNotifikasi $model): void
    {
        $model->delete();
    }
}
