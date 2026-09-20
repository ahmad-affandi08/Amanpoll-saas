<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Infrastructure\Persistence\Repositories;

use App\Domain\Notifikasi\Domain\Repositories\EskalasiTingkatLayananRepository;
use App\Domain\Notifikasi\Infrastructure\Persistence\Models\EskalasiTingkatLayanan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentEskalasiTingkatLayananRepository implements EskalasiTingkatLayananRepository
{
    public function temukan(string $id): ?EskalasiTingkatLayanan
    {
        return EskalasiTingkatLayanan::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return EskalasiTingkatLayanan::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(EskalasiTingkatLayanan $model): EskalasiTingkatLayanan
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(EskalasiTingkatLayanan $model): void
    {
        $model->delete();
    }
}
