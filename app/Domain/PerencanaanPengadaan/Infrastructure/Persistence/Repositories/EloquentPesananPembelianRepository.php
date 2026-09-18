<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\PesananPembelianRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPesananPembelianRepository implements PesananPembelianRepository
{
    public function temukan(string $id): ?PesananPembelian
    {
        return PesananPembelian::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PesananPembelian::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PesananPembelian $model): PesananPembelian
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PesananPembelian $model): void
    {
        $model->delete();
    }
}
