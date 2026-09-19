<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persetujuan\Application\Actions\BatalkanPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BatalkanPermintaanMutasiAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly BatalkanPermintaanPersetujuan $batalkanPermintaanPersetujuan,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PermintaanMutasiAset $permintaan): PermintaanMutasiAset
    {
        if (!in_array($permintaan->Status, [PermintaanMutasiAset::STATUS_DRAFT, PermintaanMutasiAset::STATUS_MENUNGGU], true)) {
            throw new AturanBisnisDilanggar('Hanya permintaan berstatus draft atau menunggu yang dapat dibatalkan.');
        }

        $this->transaksi->jalankan(function () use ($permintaan): void {
            if ($permintaan->Status === PermintaanMutasiAset::STATUS_MENUNGGU) {
                $permintaanPersetujuan = PermintaanPersetujuan::query()
                    ->where('JenisEntitas', SubmitPermintaanMutasiAset::JENIS_ENTITAS)
                    ->where('EntitasId', $permintaan->Id)
                    ->where('Status', PermintaanPersetujuan::STATUS_MENUNGGU)
                    ->first();

                if ($permintaanPersetujuan) {
                    $this->batalkanPermintaanPersetujuan->jalankan($permintaanPersetujuan);
                }
            }

            $permintaan->Status = PermintaanMutasiAset::STATUS_DIBATALKAN;
            $permintaan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PermintaanMutasiAset.Dibatalkan',
            jenisEntitas: SubmitPermintaanMutasiAset::JENIS_ENTITAS,
            entitasId: $permintaan->Id,
        );

        return $permintaan->refresh();
    }
}
