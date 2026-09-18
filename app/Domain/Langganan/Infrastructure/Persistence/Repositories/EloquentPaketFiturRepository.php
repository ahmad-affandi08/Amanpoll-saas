<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Infrastructure\Persistence\Repositories;

use App\Domain\Langganan\Domain\Repositories\PaketFiturRepository;
use App\Domain\Langganan\Infrastructure\Persistence\Models\PaketFitur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPaketFiturRepository implements PaketFiturRepository
{
    public function temukan(string $id): ?PaketFitur
    {
        return PaketFitur::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return PaketFitur::query()->latest('Id')->paginate($perHalaman);
    }

    public function simpan(PaketFitur $model): PaketFitur
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(PaketFitur $model): void
    {
        $model->delete();
    }
}
