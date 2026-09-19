<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Entitas\RegistriEntitas;
use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;

final class TambahKomentar
{
    public function __construct(
        private readonly KomentarEntitasRepository $komentarEntitasRepository,
        private readonly RegistriEntitas $registriEntitas,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(
        string $jenisEntitas,
        string $entitasId,
        string $isi,
        ?string $indukKomentarId,
        string $pembuatId,
    ): KomentarEntitas {
        $this->registriEntitas->cariEntitas($jenisEntitas, $entitasId);

        $komentar = $this->komentarEntitasRepository->simpan(new KomentarEntitas([
            'JenisEntitas' => $jenisEntitas,
            'EntitasId' => $entitasId,
            'IndukKomentarId' => $indukKomentarId,
            'Isi' => $isi,
            'DibuatOleh' => $pembuatId,
        ]));

        $this->layananAudit->catat(
            aksi: 'Komentar.Ditambahkan',
            jenisEntitas: $jenisEntitas,
            entitasId: $entitasId,
            dataSesudah: ['KomentarId' => $komentar->Id, 'Isi' => $isi],
        );

        return $komentar;
    }
}
