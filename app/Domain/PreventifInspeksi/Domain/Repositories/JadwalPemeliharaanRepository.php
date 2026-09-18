<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JadwalPemeliharaan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JadwalPemeliharaanRepository
{
    public function temukan(string $id): ?JadwalPemeliharaan;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(JadwalPemeliharaan $model): JadwalPemeliharaan;
    public function hapus(JadwalPemeliharaan $model): void;
}
