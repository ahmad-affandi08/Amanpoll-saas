<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Domain\Repositories;

use App\Domain\Persediaan\Infrastructure\Persistence\Models\KompatibilitasSukuCadang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KompatibilitasSukuCadangRepository
{
    public function temukan(string $id): ?KompatibilitasSukuCadang;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(KompatibilitasSukuCadang $model): KompatibilitasSukuCadang;

    public function hapus(KompatibilitasSukuCadang $model): void;
}
