<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\LokasiGudang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LokasiGudangRepository
{
    public function temukan(string $id): ?LokasiGudang;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(LokasiGudang $model): LokasiGudang;

    public function hapus(LokasiGudang $model): void;
}
