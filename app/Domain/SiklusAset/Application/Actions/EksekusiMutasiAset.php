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

/** Terpisah dari SubmitPermintaanMutasiAset/persetujuan secara sengaja -- persetujuan hanya OTORISASI. */
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

        // Baris yang ditolak pemegang aset dilewati, bukan menggagalkan seluruh
        // permintaan; Menunggu tetap ikut supaya permintaan yang tidak melewati
        // keputusan per aset berjalan seperti sebelumnya.
        $detailMenunggu = $permintaan->detailMutasiAset()
            ->whereIn('Status', StatusDetailMutasiAset::nilaiDapatDieksekusi())
            ->with('aset')
            ->get();

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

                // Mutasi boleh hanya menyebut unit tujuan (BuatPermintaanMutasiAset
                // menerimanya). Tanpa pengaman ini lokasi aset ditimpa null: alatnya
                // hilang dari peta lokasi, dan riwayatnya mencatat pindah ke "tidak
                // di mana pun". Lokasinya tetap, dan riwayat tetap ditulis supaya
                // peristiwa mutasinya tidak lenyap dari jejak alat.
                $lokasiTujuanId = $permintaan->LokasiTujuanId ?? $lokasiAsalId;

                RiwayatLokasiAset::create([
                    'AsetId' => $aset->Id,
                    'LokasiAsalId' => $lokasiAsalId,
                    'LokasiTujuanId' => $lokasiTujuanId,
                    'JenisPerpindahan' => JenisRiwayatLokasiAset::Mutasi->value,
                    'Alasan' => $permintaan->Alasan,
                    'DipindahkanOleh' => $dieksekusiOleh,
                    'DipindahkanPada' => now(),
                ]);

                $aset->LokasiId = $lokasiTujuanId;
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
