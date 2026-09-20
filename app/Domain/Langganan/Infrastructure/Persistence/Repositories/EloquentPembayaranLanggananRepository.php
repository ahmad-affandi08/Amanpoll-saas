<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\PembayaranLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPembayaranLanggananRepository implements PembayaranLanggananRepository
{
    public function temukan(string $id): ?PembayaranLangganan
    {
        return PembayaranLangganan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PembayaranLangganan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PembayaranLangganan $model): PembayaranLangganan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PembayaranLangganan $model): void
    {
        $model->delete();
    }
}
