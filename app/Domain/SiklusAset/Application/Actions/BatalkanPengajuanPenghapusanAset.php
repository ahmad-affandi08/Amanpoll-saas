<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persetujuan\Application\Actions\BatalkanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BatalkanPengajuanPenghapusanAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly BatalkanPermintaanPersetujuan $batalkanPermintaanPersetujuan,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PengajuanPenghapusanAset $pengajuan): PengajuanPenghapusanAset
    {
        if (! in_array($pengajuan->Status, [PengajuanPenghapusanAset::STATUS_DRAFT, PengajuanPenghapusanAset::STATUS_MENUNGGU], true)) {
            throw new AturanBisnisDilanggar('Hanya pengajuan berstatus draft atau menunggu yang dapat dibatalkan.');
        }

        $this->transaksi->jalankan(function () use ($pengajuan): void {
            if ($pengajuan->Status === PengajuanPenghapusanAset::STATUS_MENUNGGU) {
                $permintaanPersetujuan = PermintaanPersetujuan::query()
                    ->where('JenisEntitas', SubmitPengajuanPenghapusanAset::JENIS_ENTITAS)
                    ->where('EntitasId', $pengajuan->Id)
                    ->where('Status', PermintaanPersetujuan::STATUS_MENUNGGU)
                    ->first();

                if ($permintaanPersetujuan) {
                    $this->batalkanPermintaanPersetujuan->jalankan($permintaanPersetujuan);
                }
            }

            $pengajuan->Status = PengajuanPenghapusanAset::STATUS_DIBATALKAN;
            $pengajuan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PengajuanPenghapusanAset.Dibatalkan',
            jenisEntitas: SubmitPengajuanPenghapusanAset::JENIS_ENTITAS,
            entitasId: $pengajuan->Id,
        );

        return $pengajuan->refresh();
    }
}
