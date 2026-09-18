<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Repositories;

use App\Domain\PerencanaanPengadaan\Domain\Repositories\TransaksiAnggaranRepository;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\TransaksiAnggaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTransaksiAnggaranRepository implements TransaksiAnggaranRepository
{
    public function temukan(string $id): ?TransaksiAnggaran
    {
        return TransaksiAnggaran::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TransaksiAnggaran::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TransaksiAnggaran $model): TransaksiAnggaran
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(TransaksiAnggaran $model): void
    {
        $model->delete();
    }
}
