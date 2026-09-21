<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persediaan\Application\Services\LayananSaldoReservasi;
use App\Domain\Persediaan\Domain\Enums\StatusReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class LepaskanReservasiSukuCadang
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananSaldoReservasi $layananSaldoReservasi,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(ReservasiSukuCadang $reservasi): ReservasiSukuCadang
    {
        if ($reservasi->Status !== StatusReservasiSukuCadang::Aktif->value) {
            throw new AturanBisnisDilanggar('Hanya reservasi aktif yang bisa dilepas.');
        }

        $this->transaksi->jalankan(function () use ($reservasi): void {
            $this->layananSaldoReservasi->ubahDitahan(
                $reservasi->OrganisasiId,
                $reservasi->GudangId,
                $reservasi->SukuCadangId,
                -1 * (float) $reservasi->Jumlah,
            );

            $reservasi->Status = StatusReservasiSukuCadang::Dilepas->value;
            $reservasi->save();
        });

        $this->layananAudit->catat(
            aksi: 'ReservasiSukuCadang.Dilepas',
            jenisEntitas: 'ReservasiSukuCadang',
            entitasId: $reservasi->Id,
            dataSesudah: ['Status' => $reservasi->Status],
        );

        return $reservasi->refresh();
    }
}
