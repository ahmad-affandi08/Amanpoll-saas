<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Repositories;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\PelaksanaanKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PelaksanaanKalibrasiRepository
{
    public function temukan(string $id): ?PelaksanaanKalibrasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PelaksanaanKalibrasi $model): PelaksanaanKalibrasi;
    public function hapus(PelaksanaanKalibrasi $model): void;
}
