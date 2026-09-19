<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persetujuan\Application\Actions\AjukanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\AlurPersetujuan;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class SubmitPermintaanMutasiAset
{
    public const JENIS_ENTITAS = 'PermintaanMutasiAset';

    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly AjukanPermintaanPersetujuan $ajukanPermintaanPersetujuan,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PermintaanMutasiAset $permintaan, string $dimintaOleh): PermintaanMutasiAset
    {
        if ($permintaan->Status !== PermintaanMutasiAset::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Hanya permintaan berstatus draft yang bisa disubmit.');
        }

        if (!$permintaan->detailMutasiAset()->exists()) {
            throw new AturanBisnisDilanggar('Tambahkan minimal satu aset sebelum submit.');
        }

        $alurPersetujuan = AlurPersetujuan::query()
            ->where('JenisEntitas', self::JENIS_ENTITAS)
            ->where('Aktif', true)
            ->first();

        if (!$alurPersetujuan) {
            throw new AturanBisnisDilanggar('Belum ada alur persetujuan aktif untuk mutasi aset. Hubungi administrator.');
        }

        $this->transaksi->jalankan(function () use ($permintaan, $alurPersetujuan, $dimintaOleh): void {
            $this->ajukanPermintaanPersetujuan->jalankan($alurPersetujuan, $permintaan->Id, null, $dimintaOleh);

            $permintaan->Status = PermintaanMutasiAset::STATUS_MENUNGGU;
            $permintaan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PermintaanMutasiAset.Disubmit',
            jenisEntitas: self::JENIS_ENTITAS,
            entitasId: $permintaan->Id,
            dataSesudah: ['Status' => $permintaan->Status],
        );

        return $permintaan->refresh();
    }
}
