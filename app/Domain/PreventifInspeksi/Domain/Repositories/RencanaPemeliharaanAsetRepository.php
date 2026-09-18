<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RencanaPemeliharaanAsetRepository
{
    public function temukan(string $id): ?RencanaPemeliharaanAset;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(RencanaPemeliharaanAset $model): RencanaPemeliharaanAset;
    public function hapus(RencanaPemeliharaanAset $model): void;
}
