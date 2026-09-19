<?php

declare(strict_types=1);

namespace App\Domain\Kolaborasi\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Kolaborasi\Domain\Repositories\KomentarEntitasRepository;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\KomentarEntitas;

final class UbahKomentar
{
    public function __construct(
        private readonly KomentarEntitasRepository $komentarEntitasRepository,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(KomentarEntitas $komentar, string $isiBaru): KomentarEntitas
    {
        $isiLama = $komentar->Isi;
        $komentar->Isi = $isiBaru;
        $komentar = $this->komentarEntitasRepository->simpan($komentar);

        $this->layananAudit->catat(
            aksi: 'Komentar.Diubah',
            jenisEntitas: $komentar->JenisEntitas,
            entitasId: $komentar->EntitasId,
            dataSebelum: ['KomentarId' => $komentar->Id, 'Isi' => $isiLama],
            dataSesudah: ['KomentarId' => $komentar->Id, 'Isi' => $isiBaru],
        );

        return $komentar;
    }
}
