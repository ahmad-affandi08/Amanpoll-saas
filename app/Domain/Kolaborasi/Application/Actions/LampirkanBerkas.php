<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Domain\Repositories\LampiranEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;

final class LampirkanBerkas
{
    public function __construct(
        private readonly LampiranEntitasRepository $lampiranEntitasRepository,
        private readonly RegistriEntitas $registriEntitas,
    ) {}

    public function jalankan(
        string $jenisEntitas,
        string $entitasId,
        string $berkasId,
        ?string $kategori,
        ?string $keterangan,
        ?string $pembuatId,
    ): LampiranEntitas {
        // Melempar DataTidakDitemukan (404) kalau entitas tidak dikenal atau lintas organisasi.
        $this->registriEntitas->cariEntitas($jenisEntitas, $entitasId);

        return $this->lampiranEntitasRepository->simpan(new LampiranEntitas([
            'JenisEntitas' => $jenisEntitas,
            'EntitasId' => $entitasId,
            'BerkasId' => $berkasId,
            'Kategori' => $kategori,
            'Keterangan' => $keterangan,
            'DibuatOleh' => $pembuatId,
        ]));
    }
}
