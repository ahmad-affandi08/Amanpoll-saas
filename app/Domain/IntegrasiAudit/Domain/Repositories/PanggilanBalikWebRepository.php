<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\PanggilanBalikWeb;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PanggilanBalikWebRepository
{
    public function temukan(string $id): ?PanggilanBalikWeb;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PanggilanBalikWeb $model): PanggilanBalikWeb;

    public function hapus(PanggilanBalikWeb $model): void;
}
