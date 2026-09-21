<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persetujuan\Application\Actions\BatalkanPermintaanPersetujuan;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
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
        if (! in_array($permintaan->Status, [StatusPermintaanMutasiAset::Draft->value, StatusPermintaanMutasiAset::Menunggu->value], true)) {
            throw new AturanBisnisDilanggar('Hanya permintaan berstatus draft atau menunggu yang dapat dibatalkan.');
        }

        $this->transaksi->jalankan(function () use ($permintaan): void {
            if ($permintaan->Status === StatusPermintaanMutasiAset::Menunggu->value) {
                $permintaanPersetujuan = PermintaanPersetujuan::query()
                    ->where('JenisEntitas', SubmitPermintaanMutasiAset::JENIS_ENTITAS)
                    ->where('EntitasId', $permintaan->Id)
                    ->where('Status', StatusPermintaanPersetujuan::Menunggu->value)
                    ->first();

                if ($permintaanPersetujuan) {
                    $this->batalkanPermintaanPersetujuan->jalankan($permintaanPersetujuan);
                }
            }

            $permintaan->Status = StatusPermintaanMutasiAset::Dibatalkan->value;
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
