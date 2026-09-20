<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Repositories;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\HasilTitikUkurKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface HasilTitikUkurKalibrasiRepository
{
    public function temukan(string $id): ?HasilTitikUkurKalibrasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(HasilTitikUkurKalibrasi $model): HasilTitikUkurKalibrasi;

    public function hapus(HasilTitikUkurKalibrasi $model): void;
}
