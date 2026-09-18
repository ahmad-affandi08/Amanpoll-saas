<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MutasiStokRepository
{
    public function temukan(string $id): ?MutasiStok;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(MutasiStok $model): MutasiStok;
    public function hapus(MutasiStok $model): void;
}
