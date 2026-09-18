<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\ButirTemplatDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ButirTemplatDaftarPeriksaRepository
{
    public function temukan(string $id): ?ButirTemplatDaftarPeriksa;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(ButirTemplatDaftarPeriksa $model): ButirTemplatDaftarPeriksa;
    public function hapus(ButirTemplatDaftarPeriksa $model): void;
}
