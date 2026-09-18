<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Domain\Repositories;

use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CatatanAuditRepository
{
    public function temukan(string $id): ?CatatanAudit;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(CatatanAudit $model): CatatanAudit;
    public function hapus(CatatanAudit $model): void;
}
