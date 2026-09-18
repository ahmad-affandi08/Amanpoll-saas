<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Domain\Platform\Domain\Repositories\KategoriLokasiRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKategoriLokasiRepository implements KategoriLokasiRepository
{
    public function temukan(string $id): ?KategoriLokasi
    {
        return KategoriLokasi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KategoriLokasi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KategoriLokasi $model): KategoriLokasi
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KategoriLokasi $model): void
    {
        $model->delete();
    }
}
