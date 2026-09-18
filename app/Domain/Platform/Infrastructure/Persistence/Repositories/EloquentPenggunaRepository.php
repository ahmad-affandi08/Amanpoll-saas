<?php

declare(strict_types=1);

namespace App\Domain\Platform\Infrastructure\Persistence\Repositories;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentPenggunaRepository implements PenggunaRepository
{
    public function __construct(private readonly KonteksOrganisasi $konteks) {}

    public function temukan(string $id): ?Pengguna
    {
        return Pengguna::query()->where('OrganisasiId', $this->konteks->wajibId())->find($id);
    }

    public function paginasi(int $perHalaman = 25): LengthAwarePaginator
    {
        return Pengguna::query()->where('OrganisasiId', $this->konteks->wajibId())->latest('DibuatPada')->paginate($perHalaman);
    }

    public function simpan(Pengguna $model): Pengguna
    {
        $model->save();
        return $model->refresh();
    }

    public function hapus(Pengguna $model): void
    {
        $model->delete();
    }
}
