<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PembayaranPenyediaRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PembayaranPenyedia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPembayaranPenyediaRepository implements PembayaranPenyediaRepository
{
    public function temukan(string $id): ?PembayaranPenyedia
    {
        return PembayaranPenyedia::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PembayaranPenyedia::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PembayaranPenyedia $model): PembayaranPenyedia
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PembayaranPenyedia $model): void
    {
        $model->delete();
    }
}
