<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Domain\Repositories;

use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LampiranEntitasRepository
{
    public function temukan(string $id): ?LampiranEntitas;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(LampiranEntitas $model): LampiranEntitas;
    public function hapus(LampiranEntitas $model): void;
}
