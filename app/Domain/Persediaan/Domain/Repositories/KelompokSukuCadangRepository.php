<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KelompokSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KelompokSukuCadangRepository
{
    public function temukan(string $id): ?KelompokSukuCadang;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KelompokSukuCadang $model): KelompokSukuCadang;
    public function hapus(KelompokSukuCadang $model): void;
}
