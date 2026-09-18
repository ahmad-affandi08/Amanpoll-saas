<?php

declare(strict_types=1);

namespace App\Domain\Platform\Domain\Repositories;

use App\Domain\Platform\Infrastructure\Persistence\Models\KonfigurasiOrganisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KonfigurasiOrganisasiRepository
{
    public function temukan(string $id): ?KonfigurasiOrganisasi;
    public function paginasi(int $perHalaman = 25): LengthAwarePaginator;
    public function simpan(KonfigurasiOrganisasi $model): KonfigurasiOrganisasi;
    public function hapus(KonfigurasiOrganisasi $model): void;
}
