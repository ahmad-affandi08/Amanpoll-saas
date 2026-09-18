<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PengirimanPanggilanBalikWeb;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PengirimanPanggilanBalikWebRepository
{
    public function temukan(string $id): ?PengirimanPanggilanBalikWeb;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(PengirimanPanggilanBalikWeb $model): PengirimanPanggilanBalikWeb;
    public function hapus(PengirimanPanggilanBalikWeb $model): void;
}
