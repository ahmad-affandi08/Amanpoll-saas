<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\LokasiGudangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentLokasiGudangRepository implements LokasiGudangRepository
{
    public function temukan(string $id): ?LokasiGudang
    {
        return LokasiGudang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return LokasiGudang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(LokasiGudang $model): LokasiGudang
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(LokasiGudang $model): void
    {
        $model->delete();
    }
}
