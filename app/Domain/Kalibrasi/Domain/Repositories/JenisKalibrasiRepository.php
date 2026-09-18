<?php

declare(strict_types=1);

namespace App\Domain\Kalibrasi\Domain\Repositories;

use App\Domain\Kalibrasi\Infrastructure\Persistence\Models\JenisKalibrasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JenisKalibrasiRepository
{
    public function temukan(string $id): ?JenisKalibrasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(JenisKalibrasi $model): JenisKalibrasi;
    public function hapus(JenisKalibrasi $model): void;
}
