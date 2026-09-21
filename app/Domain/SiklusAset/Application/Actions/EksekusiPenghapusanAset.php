<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Domain\Enums\StatusAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailPenghapusanAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPengajuanPenghapusanAset;
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
        if ($pengajuan->Status === StatusPengajuanPenghapusanAset::Selesai->value) {
            return $pengajuan;
        }

        if ($pengajuan->Status !== StatusPengajuanPenghapusanAset::Disetujui->value) {
            throw new AturanBisnisDilanggar('Penghapusan hanya bisa dieksekusi setelah disetujui.');
        }

        $detailMenunggu = $pengajuan->detailPenghapusanAset()->where('Status', StatusDetailPenghapusanAset::Menunggu->value)->with('aset')->get();

        if ($detailMenunggu->isEmpty()) {
            throw new AturanBisnisDilanggar('Tidak ada aset yang perlu dieksekusi.');
        }

        $this->transaksi->jalankan(function () use ($detailMenunggu): void {
            foreach ($detailMenunggu as $detail) {
                /** @var Aset|null $aset */
                $aset = $detail->aset;

                if (! $aset) {
                    $detail->Status = StatusDetailPenghapusanAset::Dibatalkan->value;
                    $detail->Catatan = trim(($detail->Catatan ?? '')."\nAset tidak ditemukan saat eksekusi.");
                    $detail->save();

                    continue;
                }

                $aset->Status = StatusAset::Diarsipkan->value;
                $aset->save();
                $aset->delete();

                $detail->Status = StatusDetailPenghapusanAset::Selesai->value;
                $detail->save();
            }
        });

        $pengajuan->Status = StatusPengajuanPenghapusanAset::Selesai->value;
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
