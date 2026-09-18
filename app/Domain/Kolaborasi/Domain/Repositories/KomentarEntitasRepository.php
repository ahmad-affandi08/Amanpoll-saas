<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KomentarEntitasRepository
{
    public function temukan(string $id): ?KomentarEntitas;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KomentarEntitas $model): KomentarEntitas;
    public function hapus(KomentarEntitas $model): void;
}
