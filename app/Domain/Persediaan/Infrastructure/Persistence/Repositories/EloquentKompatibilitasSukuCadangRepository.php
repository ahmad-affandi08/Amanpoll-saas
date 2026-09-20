<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\KompatibilitasSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKompatibilitasSukuCadangRepository implements KompatibilitasSukuCadangRepository
{
    public function temukan(string $id): ?KompatibilitasSukuCadang
    {
        return KompatibilitasSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KompatibilitasSukuCadang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KompatibilitasSukuCadang $model): KompatibilitasSukuCadang
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KompatibilitasSukuCadang $model): void
    {
        $model->delete();
    }
}
