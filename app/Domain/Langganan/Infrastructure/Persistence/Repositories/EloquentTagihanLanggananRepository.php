<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\TagihanLanggananRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\TagihanLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentTagihanLanggananRepository implements TagihanLanggananRepository
{
    public function temukan(string $id): ?TagihanLangganan
    {
        return TagihanLangganan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return TagihanLangganan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(TagihanLangganan $model): TagihanLangganan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(TagihanLangganan $model): void
    {
        $model->delete();
    }
}
