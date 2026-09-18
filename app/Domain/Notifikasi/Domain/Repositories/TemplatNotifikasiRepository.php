<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Repositories;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\TemplatNotifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TemplatNotifikasiRepository
{
    public function temukan(string $id): ?TemplatNotifikasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(TemplatNotifikasi $model): TemplatNotifikasi;
    public function hapus(TemplatNotifikasi $model): void;
}
