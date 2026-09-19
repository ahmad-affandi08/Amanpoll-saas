<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;

final class HapusKomentar
{
    public function __construct(
        private readonly KomentarEntitasRepository $komentarEntitasRepository,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(KomentarEntitas $komentar): void
    {
        $this->komentarEntitasRepository->hapus($komentar);

        $this->layananAudit->catat(
            aksi: 'Komentar.Dihapus',
            jenisEntitas: $komentar->JenisEntitas,
            entitasId: $komentar->EntitasId,
            dataSebelum: ['KomentarId' => $komentar->Id, 'Isi' => $komentar->Isi],
        );
    }
}
