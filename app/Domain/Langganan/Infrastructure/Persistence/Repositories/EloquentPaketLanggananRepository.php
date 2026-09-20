<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\PaketLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPaketLanggananRepository implements PaketLanggananRepository
{
    public function temukan(string $id): ?PaketLangganan
    {
        return PaketLangganan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PaketLangganan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(PaketLangganan $model): PaketLangganan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(PaketLangganan $model): void
    {
        $model->delete();
    }
}
