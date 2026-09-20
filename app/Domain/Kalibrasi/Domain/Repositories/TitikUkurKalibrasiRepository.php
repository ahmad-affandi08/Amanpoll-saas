<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Repositories;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\TitikUkurKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TitikUkurKalibrasiRepository
{
    public function temukan(string $id): ?TitikUkurKalibrasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(TitikUkurKalibrasi $model): TitikUkurKalibrasi;

    public function hapus(TitikUkurKalibrasi $model): void;
}
