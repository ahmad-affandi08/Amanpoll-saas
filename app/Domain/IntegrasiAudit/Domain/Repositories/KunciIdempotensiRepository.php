<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\KunciIdempotensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KunciIdempotensiRepository
{
    public function temukan(string $id): ?KunciIdempotensi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KunciIdempotensi $model): KunciIdempotensi;
    public function hapus(KunciIdempotensi $model): void;
}
