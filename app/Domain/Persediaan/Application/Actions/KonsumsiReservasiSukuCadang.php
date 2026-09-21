<?php

declare(strict_types=1);

namespace App\Domain\Persediaan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Persediaan\Application\Services\LayananSaldoReservasi;
use App\Domain\Persediaan\Domain\Enums\JenisMutasiStok;
use App\Domain\Persediaan\Domain\Enums\StatusReservasiSukuCadang;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\MutasiStok;
use App\Domain\Persediaan\Infrastructure\Persistence\Models\ReservasiSukuCadang;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Mengubah reservasi menjadi pengeluaran stok sungguhan: membuat + memposting
 * MutasiStok jenis Pengeluaran (supaya JumlahTersedia benar-benar berkurang
 * dan tercatat lewat dokumen resmi), lalu melepas hold JumlahDitahan yang
 * dibuat saat reservasi dibuat.
 */
final class KonsumsiReservasiSukuCadang
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly BuatMutasiStok $buatMutasiStok,
        private readonly TambahDetailMutasiStok $tambahDetailMutasiStok,
        private readonly PostingMutasiStok $postingMutasiStok,
        private readonly LayananSaldoReservasi $layananSaldoReservasi,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(ReservasiSukuCadang $reservasi, string $dipakaiOleh): ReservasiSukuCadang
    {
        if ($reservasi->Status !== StatusReservasiSukuCadang::Aktif->value) {
            throw new AturanBisnisDilanggar('Hanya reservasi aktif yang bisa dipakai.');
        }

        $this->transaksi->jalankan(function () use ($reservasi, $dipakaiOleh): void {
            $mutasiStok = $this->buatMutasiStok->jalankan([
                'Jenis' => JenisMutasiStok::Pengeluaran->value,
                'GudangAsalId' => $reservasi->GudangId,
                'GudangTujuanId' => null,
                'ReferensiJenis' => 'ReservasiSukuCadang',
                'ReferensiId' => $reservasi->Id,
                'Catatan' => 'Konsumsi reservasi suku cadang.',
            ], $dipakaiOleh);

            $this->tambahDetailMutasiStok->jalankan($mutasiStok, [
                'SukuCadangId' => $reservasi->SukuCadangId,
                'Jumlah' => $reservasi->Jumlah,
            ]);

            $this->postingMutasiStok->jalankan($mutasiStok, $dipakaiOleh);

            $this->layananSaldoReservasi->ubahDitahan(
                $reservasi->OrganisasiId,
                $reservasi->GudangId,
                $reservasi->SukuCadangId,
                -1 * (float) $reservasi->Jumlah,
            );

            $reservasi->Status = StatusReservasiSukuCadang::Dipakai->value;
            $reservasi->save();
        });

        $this->layananAudit->catat(
            aksi: 'ReservasiSukuCadang.Dipakai',
            jenisEntitas: 'ReservasiSukuCadang',
            entitasId: $reservasi->Id,
            dataSesudah: ['Status' => $reservasi->Status],
        );

        return $reservasi->refresh();
    }
}
