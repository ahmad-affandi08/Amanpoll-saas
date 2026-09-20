<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAkses;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatatanAksesRepository
{
    public function temukan(string $id): ?CatatanAkses;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(CatatanAkses $model): CatatanAkses;

    public function hapus(CatatanAkses $model): void;
}
