<?php

declare(strict_types=1);

namespace App\Domain\Notifikasi\Domain\Repositories;

use App\Domain\Notifikasi\Infrastructure\Persistence\Models\PreferensiNotifikasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PreferensiNotifikasiRepository
{
    public function temukan(string $id): ?PreferensiNotifikasi;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PreferensiNotifikasi $model): PreferensiNotifikasi;

    public function hapus(PreferensiNotifikasi $model): void;
}
