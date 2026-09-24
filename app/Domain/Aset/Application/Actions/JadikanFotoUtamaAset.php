<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;

/**
 * Memilih foto utama aset dari galerinya (PRD 8.4 "Foto Aset"). Foto utama
 * tidak menaikkan `Versi` aset: ia bukan isian formulir, jadi formulir ubah
 * yang sedang terbuka di tempat lain tidak perlu dianggap konflik.
 */
final class JadikanFotoUtamaAset
{
    public function __construct(
        private readonly GaleriFotoAset $galeri,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(Aset $aset, Berkas $berkas): void
    {
        $this->galeri->pastikanMilik($aset, $berkas);

        $sebelumnya = $aset->FotoUtamaBerkasId;
        if ($sebelumnya === $berkas->Id) {
            return;
        }

        Aset::query()->whereKey($aset->Id)->update(['FotoUtamaBerkasId' => $berkas->Id]);
        $aset->setAttribute('FotoUtamaBerkasId', $berkas->Id);
        $aset->syncOriginalAttribute('FotoUtamaBerkasId');

        $this->audit->catat(
            'Aset.FotoUtamaDiubah',
            'Aset',
            $aset->Id,
            dataSebelum: ['FotoUtamaBerkasId' => $sebelumnya],
            dataSesudah: ['FotoUtamaBerkasId' => $berkas->Id],
        );
    }
}
