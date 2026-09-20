<?php

declare(strict_types=1);

namespace App\Domain\IntegrasiAudit\Infrastructure\Persistence\Repositories;

use App\Domain\IntegrasiAudit\Domain\Repositories\CatatanAuditRepository;
use App\Domain\IntegrasiAudit\Infrastructure\Persistence\Models\CatatanAudit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCatatanAuditRepository implements CatatanAuditRepository
{
    public function temukan(string $id): ?CatatanAudit
    {
        return CatatanAudit::query()->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return CatatanAudit::query()->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(CatatanAudit $model): CatatanAudit
    {
        $model->save();

        return $model->refresh();
    }

    public function hapus(CatatanAudit $model): void
    {
        $model->delete();
    }
}
