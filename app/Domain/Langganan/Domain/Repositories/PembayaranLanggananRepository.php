<?php

declare(strict_types=1);

namespace App\Domain\Langganan\Domain\Repositories;

use App\Domain\Langganan\Infrastructure\Persistence\Models\PembayaranLangganan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PembayaranLanggananRepository
{
    public function temukan(string $id): ?PembayaranLangganan;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(PembayaranLangganan $model): PembayaranLangganan;

    public function hapus(PembayaranLangganan $model): void;
}
