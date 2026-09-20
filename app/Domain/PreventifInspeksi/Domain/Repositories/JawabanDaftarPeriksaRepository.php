<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Domain\Repositories;

use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\JawabanDaftarPeriksa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface JawabanDaftarPeriksaRepository
{
    public function temukan(string $id): ?JawabanDaftarPeriksa;

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;

    public function simpan(JawabanDaftarPeriksa $model): JawabanDaftarPeriksa;

    public function hapus(JawabanDaftarPeriksa $model): void;
}
