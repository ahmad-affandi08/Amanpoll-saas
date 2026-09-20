<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\KunciIdempotensiRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KunciIdempotensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentKunciIdempotensiRepository implements KunciIdempotensiRepository
{
    public function temukan(string $id): ?KunciIdempotensi
    {
        return KunciIdempotensi::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return KunciIdempotensi::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(KunciIdempotensi $model): KunciIdempotensi
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(KunciIdempotensi $model): void
    {
        $model->delete();
    }
}
