<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Application\Services\GaleriFotoAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Kolaborasi\Application\Actions\HapusBerkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\Berkas;
use App\Domain\Kolaborasi\Infrastructure\Persistence\Models\LampiranEntitas;
use App\Shared\Domain\Contracts\TransaksiDatabase;

/**
 * Menghapus satu foto dari galeri aset (PRD 8.4 "Foto Aset"). Bila yang dihapus
 * foto utama, foto tertua berikutnya menggantikannya (atau kosong bila galeri
 * habis). Lampiran dan foto utama diubah di bawah kunci baris aset; berkasnya
 * dihapus sesudahnya lewat Kolaborasi, jadi salinan fisik yang dipakai bersama
 * berkas lain tetap aman (PRD 11.1).
 */
final class HapusFotoAset
{
    public function __construct(
        private readonly GaleriFotoAset $galeri,
        private readonly HapusBerkas $hapusBerkas,
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $audit,
    ) {}

    public function jalankan(Aset $aset, Berkas $berkas): void
    {
        $this->galeri->pastikanMilik($aset, $berkas);

        [$utamaSebelum, $utamaSesudah] = $this->transaksi->jalankan(function () use ($aset, $berkas): array {
            $terkunci = Aset::query()->whereKey($aset->Id)->lockForUpdate()->firstOrFail();
            $utama = $terkunci->FotoUtamaBerkasId;

            LampiranEntitas::query()
                ->where('JenisEntitas', GaleriFotoAset::JENIS_ENTITAS)
                ->where('EntitasId', $terkunci->Id)
                ->where('Kategori', GaleriFotoAset::KATEGORI)
                ->where('BerkasId', $berkas->Id)
                ->delete();

            if ($utama !== null && $utama !== $berkas->Id) {
                return [$utama, $utama];
            }

            $pengganti = $this->galeri->berikutnya($terkunci, $berkas->Id);
            Aset::query()->whereKey($terkunci->Id)->update(['FotoUtamaBerkasId' => $pengganti]);

            return [$utama, $pengganti];
        });

        $this->hapusBerkas->jalankan($berkas);

        $aset->setAttribute('FotoUtamaBerkasId', $utamaSesudah);
        $aset->syncOriginalAttribute('FotoUtamaBerkasId');

        $this->audit->catat(
            'Aset.FotoDihapus',
            'Aset',
            $aset->Id,
            dataSebelum: ['BerkasId' => $berkas->Id, 'NamaAsli' => $berkas->NamaAsli, 'FotoUtamaBerkasId' => $utamaSebelum],
            dataSesudah: ['FotoUtamaBerkasId' => $utamaSesudah],
        );
    }
}
