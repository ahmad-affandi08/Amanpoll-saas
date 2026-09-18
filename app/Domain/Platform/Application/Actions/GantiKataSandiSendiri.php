<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class GantiKataSandiSendiri
{
    public function __construct(private readonly PenggunaRepository $penggunaRepository) {}

    public function jalankan(Pengguna $pengguna, string $kataSandiBaru): void
    {
        $pengguna->KataSandi = $kataSandiBaru;
        $this->penggunaRepository->simpan($pengguna);
    }
}
