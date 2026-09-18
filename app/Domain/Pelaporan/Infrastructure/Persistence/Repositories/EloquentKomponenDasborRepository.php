<?php

declare(strict_types=1);

namespace App\Domain\Pelaporan\Infrastructure\Persistence\Repositories;

use App\Domain\Pelaporan\Domain\Repositories\KomponenDasborRepository;
use App\Domain\Pelaporan\Infrastructure\Persistence\Models\KomponenDasbor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKomponenDasborRepository implements KomponenDasborRepository
{
    public function temukan(string $id): ?KomponenDasbor
    {
        return KomponenDasbor::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KomponenDasbor::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KomponenDasbor $model): KomponenDasbor
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(KomponenDasbor $model): void
    {
        $model->delete();
    }
}
