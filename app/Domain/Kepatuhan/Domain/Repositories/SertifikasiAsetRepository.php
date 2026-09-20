<?php

declare(strict_types=1);

namespace App\Domain\Kepatuhan\Domain\Repositories;

use App\Domain\Kepatuhan\Infrastructure\Persistence\Models\SertifikasiAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SertifikasiAsetRepository
{
    public function temukan(string $id): ?SertifikasiAset;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(SertifikasiAset $model): SertifikasiAset;

    public function hapus(SertifikasiAset $model): void;
}
