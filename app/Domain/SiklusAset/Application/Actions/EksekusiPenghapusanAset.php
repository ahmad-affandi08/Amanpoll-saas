<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Aset historis TIDAK PERNAH forceDelete() -- hanya diarsipkan (Status =
 * Diarsipkan) lalu soft-delete biasa (DihapusPada), supaya seluruh riwayat
 * (lokasi, penanggung jawab, nilai, dst.) tetap tertelusuri (checklist
 * 09.04 "Larang hard-delete aset historis").
 */
final class EksekusiPenghapusanAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PengajuanPenghapusanAset $pengajuan): PengajuanPenghapusanAset
    {
        if ($pengajuan->Status === PengajuanPenghapusanAset::STATUS_SELESAI) {
            return $pengajuan;
        }

        if ($pengajuan->Status !== PengajuanPenghapusanAset::STATUS_DISETUJUI) {
            throw new AturanBisnisDilanggar('Penghapusan hanya bisa dieksekusi setelah disetujui.');
        }

        $detailMenunggu = $pengajuan->detailPenghapusanAset()->where('Status', DetailPenghapusanAset::STATUS_MENUNGGU)->with('aset')->get();

        if ($detailMenunggu->isEmpty()) {
            throw new AturanBisnisDilanggar('Tidak ada aset yang perlu dieksekusi.');
        }

        $this->transaksi->jalankan(function () use ($detailMenunggu): void {
            foreach ($detailMenunggu as $detail) {
                /** @var Aset|null $aset */
                $aset = $detail->aset;

                if (!$aset) {
                    $detail->Status = DetailPenghapusanAset::STATUS_DIBATALKAN;
                    $detail->Catatan = trim(($detail->Catatan ?? '')."\nAset tidak ditemukan saat eksekusi.");
                    $detail->save();
                    continue;
                }

                $aset->Status = Aset::STATUS_DIARSIPKAN;
                $aset->save();
                $aset->delete();

                $detail->Status = DetailPenghapusanAset::STATUS_SELESAI;
                $detail->save();
            }
        });

        $pengajuan->Status = PengajuanPenghapusanAset::STATUS_SELESAI;
        $pengajuan->DiselesaikanPada = now()->toImmutable();
        $pengajuan->save();

        $this->layananAudit->catat(
            aksi: 'PengajuanPenghapusanAset.Dieksekusi',
            jenisEntitas: 'PengajuanPenghapusanAset',
            entitasId: $pengajuan->Id,
        );

        return $pengajuan->refresh();
    }
}
