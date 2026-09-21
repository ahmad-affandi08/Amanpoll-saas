<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Domain\Aset\Domain\Enums\JenisRiwayatLokasiAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Domain\Aset\Infrastructure\Persistence\Models\RiwayatLokasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusDetailMutasiAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Terpisah dari SubmitPermintaanMutasiAset/persetujuan secara sengaja --
 * persetujuan hanya OTORISASI, perpindahan fisik aset sungguhan (dan
 * pencatatannya di sistem) adalah langkah manusia berikutnya yang bisa
 * tertunda dari waktu keputusan disetujui. Idempotency dijaga dengan
 * guard status di awal (checklist 09.02).
 */
final class EksekusiMutasiAset
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananAudit $layananAudit,
    ) {}

    public function jalankan(PermintaanMutasiAset $permintaan, string $dieksekusiOleh): PermintaanMutasiAset
    {
        if ($permintaan->Status === StatusPermintaanMutasiAset::Selesai->value) {
            return $permintaan;
        }

        if ($permintaan->Status !== StatusPermintaanMutasiAset::Disetujui->value) {
            throw new AturanBisnisDilanggar('Mutasi hanya bisa dieksekusi setelah disetujui.');
        }

        $detailMenunggu = $permintaan->detailMutasiAset()->where('Status', StatusDetailMutasiAset::Menunggu->value)->with('aset')->get();

        if ($detailMenunggu->isEmpty()) {
            throw new AturanBisnisDilanggar('Tidak ada aset yang perlu dieksekusi.');
        }

        $this->transaksi->jalankan(function () use ($permintaan, $detailMenunggu, $dieksekusiOleh): void {
            foreach ($detailMenunggu as $detail) {
                /** @var Aset|null $aset */
                $aset = $detail->aset;

                if (! $aset) {
                    $detail->Status = StatusDetailMutasiAset::Dibatalkan->value;
                    $detail->Catatan = trim(($detail->Catatan ?? '')."\nAset tidak ditemukan saat eksekusi.");
                    $detail->save();

                    continue;
                }

                $lokasiAsalId = $aset->LokasiId;

                RiwayatLokasiAset::create([
                    'AsetId' => $aset->Id,
                    'LokasiAsalId' => $lokasiAsalId,
                    'LokasiTujuanId' => $permintaan->LokasiTujuanId,
                    'JenisPerpindahan' => JenisRiwayatLokasiAset::Mutasi->value,
                    'Alasan' => $permintaan->Alasan,
                    'DipindahkanOleh' => $dieksekusiOleh,
                    'DipindahkanPada' => now(),
                ]);

                $aset->LokasiId = $permintaan->LokasiTujuanId;
                if ($permintaan->UnitTujuanId !== null) {
                    $aset->UnitOrganisasiId = $permintaan->UnitTujuanId;
                }
                $aset->save();

                $detail->Status = StatusDetailMutasiAset::Selesai->value;
                $detail->save();
            }

            $permintaan->Status = StatusPermintaanMutasiAset::Selesai->value;
            $permintaan->SelesaiPada = now()->toImmutable();
            $permintaan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PermintaanMutasiAset.Dieksekusi',
            jenisEntitas: 'PermintaanMutasiAset',
            entitasId: $permintaan->Id,
            dataSesudah: ['LokasiTujuanId' => $permintaan->LokasiTujuanId, 'UnitTujuanId' => $permintaan->UnitTujuanId],
        );

        return $permintaan->refresh();
    }
}
