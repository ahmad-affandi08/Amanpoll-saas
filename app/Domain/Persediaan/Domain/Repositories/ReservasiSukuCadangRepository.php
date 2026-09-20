<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReservasiSukuCadangRepository
{
    public function temukan(string $id): ?ReservasiSukuCadang;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(ReservasiSukuCadang $model): ReservasiSukuCadang;

    public function hapus(ReservasiSukuCadang $model): void;
}
