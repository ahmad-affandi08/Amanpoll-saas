<?php

declare(strict_types=1);

namespace App\Domain\Kontrak\Domain\Repositories;

use App\Domain\Kontrak\Infrastructure\Persistence\Models\Kontrak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KontrakRepository
{
    public function temukan(string $id): ?Kontrak;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(Kontrak $model): Kontrak;
    public function hapus(Kontrak $model): void;
}
