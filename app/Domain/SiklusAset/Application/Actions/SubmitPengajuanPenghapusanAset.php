<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class SubmitPengajuanPenghapusanAset
{
    public const JENIS_ENTITAS = 'PengajuanPenghapusanAset';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly AjukanPermintaanPersetujuan $ajukanPermintaanPersetujuan,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PengajuanPenghapusanAset $pengajuan, string $diajukanOleh): PengajuanPenghapusanAset
    {
        if ($pengajuan->Status !== PengajuanPenghapusanAset::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Hanya pengajuan berstatus draft yang bisa disubmit.');
        }

        if (! $pengajuan->detailPenghapusanAset()->exists()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu aset sebelum submit.');
        }

        $alurPersetujuan = AlurPersetujuan::query()
            ->where('JenisEntitas', self::JENIS_ENTITAS)
            ->where('Aktif', true)
            ->first();

        if (! $alurPersetujuan) {
            throw new AturanBisnisDilanggar('Belum ada alur persetujuan aktif untuk penghapusan aset. Hubungi administrator.');
        }

        $this->transaksi->jalankan(function () use ($pengajuan, $alurPersetujuan, $diajukanOleh): void {
            $this->ajukanPermintaanPersetujuan->jalankan($alurPersetujuan, $pengajuan->Id, null, $diajukanOleh);

            $pengajuan->Status = PengajuanPenghapusanAset::STATUS_MENUNGGU;
            $pengajuan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PengajuanPenghapusanAset.Disubmit',
            jenisEntitas: self::JENIS_ENTITAS,
            entitasId: $pengajuan->Id,
            dataSesudah: ['Status' => $pengajuan->Status],
        );

        return $pengajuan->refresh();
    }
}
