<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Domain\Kolaborasi\Domain\Repositories\EntitasTagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;

final class LepaskanTagDariEntitas
{
    public function __construct(private readonly EntitasTagRepository $entitasTagRepository) {}

    public function jalankan(EntitasTag $entitasTag): void
    {
        $this->entitasTagRepository->hapus($entitasTag);
    }
}
