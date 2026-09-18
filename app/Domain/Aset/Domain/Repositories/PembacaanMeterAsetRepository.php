<?php

declare(strict_types=1);

namespace App\Domain\Aset\Domain\Repositories;

use App\Domain\Aset\Infrastructure\Persistence\Models\PembacaanMeterAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PembacaanMeterAsetRepository
{
    public function temukan(string $id): ?PembacaanMeterAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PembacaanMeterAset $model): PembacaanMeterAset;
    public function hapus(PembacaanMeterAset $model): void;
}
