<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\TemplatDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TemplatDaftarPeriksaRepository
{
    public function temukan(string $id): ?TemplatDaftarPeriksa;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(TemplatDaftarPeriksa $model): TemplatDaftarPeriksa;

    public function hapus(TemplatDaftarPeriksa $model): void;
}
