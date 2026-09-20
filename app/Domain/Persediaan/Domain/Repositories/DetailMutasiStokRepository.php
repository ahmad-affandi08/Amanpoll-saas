<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\DetailMutasiStok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DetailMutasiStokRepository
{
    public function temukan(string $id): ?DetailMutasiStok;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(DetailMutasiStok $model): DetailMutasiStok;

    public function hapus(DetailMutasiStok $model): void;
}
