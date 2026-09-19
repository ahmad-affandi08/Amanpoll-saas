<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persediaan\Application\Services\LayananSaldoReservasi;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class BuatReservasiSukuCadang
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananSaldoReservasi $layananSaldoReservasi,
        private readonly LayananAudit $layananAudit,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function jalankan(array $data, string $dibuatOleh): ReservasiSukuCadang
    {
        $jumlah = (float) $data['Jumlah'];

        if ($jumlah <= 0) {
            throw new AturanBisnisDilanggar('Jumlah reservasi harus lebih besar dari nol.');
        }

        $reservasi = $this->transaksi->jalankan(function () use ($data, $jumlah, $dibuatOleh): ReservasiSukuCadang {
            $tersediaBersih = $this->layananSaldoReservasi->tersediaBersih($data['GudangId'], $data['SukuCadangId']);

            if ($jumlah > $tersediaBersih) {
                throw new AturanBisnisDilanggar("Stok tersedia tidak cukup untuk reservasi ini (tersedia bersih: {$tersediaBersih}).");
            }

            $data['DibuatOleh'] = $dibuatOleh;
            $data['Status'] = ReservasiSukuCadang::STATUS_AKTIF;

            /** @var ReservasiSukuCadang $reservasi */
            $reservasi = ReservasiSukuCadang::create($data);

            $this->layananSaldoReservasi->ubahDitahan($reservasi->OrganisasiId, $reservasi->GudangId, $reservasi->SukuCadangId, $jumlah);

            return $reservasi;
        });

        $this->layananAudit->catat(
            aksi: 'ReservasiSukuCadang.Direservasi',
            jenisEntitas: 'ReservasiSukuCadang',
            entitasId: $reservasi->Id,
            dataSesudah: ['Jumlah' => (string) $reservasi->Jumlah, 'Status' => $reservasi->Status],
        );

        return $reservasi;
    }
}
