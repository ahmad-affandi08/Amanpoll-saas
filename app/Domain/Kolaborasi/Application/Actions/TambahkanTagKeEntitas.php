<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Domain\Repositories\EntitasTagRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\EntitasTag;

final class TambahkanTagKeEntitas
{
    public function __construct(
        private readonly EntitasTagRepository $entitasTagRepository,
        private readonly RegistriEntitas $registriEntitas,
    ) {}

    public function jalankan(string $tagId, string $jenisEntitas, string $entitasId): EntitasTag
    {
        $this->registriEntitas->cariEntitas($jenisEntitas, $entitasId);

        $sudahAda = EntitasTag::query()
            ->where('TagId', $tagId)
            ->where('JenisEntitas', $jenisEntitas)
            ->where('EntitasId', $entitasId)
            ->first();

        if ($sudahAda) {
            return $sudahAda;
        }

        return $this->entitasTagRepository->simpan(new EntitasTag([
            'TagId' => $tagId,
            'JenisEntitas' => $jenisEntitas,
            'EntitasId' => $entitasId,
        ]));
    }
}
