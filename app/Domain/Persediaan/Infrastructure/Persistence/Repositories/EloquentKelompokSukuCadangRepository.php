<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Infrastructure\Persistence\Repositories;

use App\Domain\Persediaan\Domain\Repositories\KelompokSukuCadangRepository;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKelompokSukuCadangRepository implements KelompokSukuCadangRepository
{
    public function temukan(string $id): ?KelompokSukuCadang
    {
        return KelompokSukuCadang::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KelompokSukuCadang::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KelompokSukuCadang $model): KelompokSukuCadang
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KelompokSukuCadang $model): void
    {
        $model->delete();
    }
}
