<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Entitas\RegistriEntitas;
use App\Domain\Persetujuan\Application\Services\LayananNotifikasiPersetujuan;
use App\Domain\Persetujuan\Application\Services\LayananPenyetuju;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

/**
 * Perilaku penolakan bersifat fail-fast: satu penolakan di tahap manapun
 * langsung mengakhiri seluruh PermintaanPersetujuan (tidak menunggu
 * kuorum penolakan) -- skema TahapPersetujuan tidak punya kolom untuk
 * konfigurasi perilaku penolakan lain, jadi ini dipilih sebagai default
 * yang paling umum dipakai mesin approval.
 */
final class TolakPermintaanPersetujuan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananPenyetuju $layananPenyetuju,
        private readonly RegistriEntitas $registriEntitas,
        private readonly LayananAudit $layananAudit,
        private readonly LayananNotifikasiPersetujuan $layananNotifikasiPersetujuan,
    ) {}

    public function jalankan(PermintaanPersetujuan $permintaan, Pengguna $penyetuju, ?string $catatan): PermintaanPersetujuan
    {
        if ($permintaan->Status !== PermintaanPersetujuan::STATUS_MENUNGGU) {
            throw new AturanBisnisDilanggar('Permintaan persetujuan ini sudah selesai.');
        }

        $tahap = TahapPersetujuan::query()
            ->where('AlurPersetujuanId', $permintaan->AlurPersetujuanId)
            ->where('Urutan', $permintaan->TahapSaatIni)
            ->firstOrFail();

        $entitas = $this->registriEntitas->cariEntitas($permintaan->JenisEntitas, $permintaan->EntitasId);

        if (!$this->layananPenyetuju->bolehMemutuskan($tahap, $entitas, $penyetuju, $permintaan->DimintaOleh)) {
            throw new AksesDitolak('Anda tidak berhak menolak tahap ini.');
        }

        if (KeputusanPersetujuan::query()->where('PermintaanPersetujuanId', $permintaan->Id)->where('TahapPersetujuanId', $tahap->Id)->where('PenyetujuId', $penyetuju->Id)->exists()) {
            throw new KonflikData('Anda sudah memutuskan tahap ini sebelumnya.');
        }

        $this->transaksi->jalankan(function () use ($permintaan, $tahap, $penyetuju, $catatan): void {
            KeputusanPersetujuan::create([
                'PermintaanPersetujuanId' => $permintaan->Id,
                'TahapPersetujuanId' => $tahap->Id,
                'PenyetujuId' => $penyetuju->Id,
                'Keputusan' => KeputusanPersetujuan::KEPUTUSAN_DITOLAK,
                'Catatan' => $catatan,
                'DiputuskanPada' => now(),
            ]);

            $permintaan->Status = PermintaanPersetujuan::STATUS_DITOLAK;
            $permintaan->SelesaiPada = now()->toImmutable();
            $permintaan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PermintaanPersetujuan.Ditolak',
            jenisEntitas: $permintaan->JenisEntitas,
            entitasId: $permintaan->EntitasId,
            dataSesudah: ['PermintaanPersetujuanId' => $permintaan->Id, 'TahapPersetujuanId' => $tahap->Id, 'PenyetujuId' => $penyetuju->Id, 'Catatan' => $catatan],
        );

        $this->layananNotifikasiPersetujuan->beriTahuSelesai(
            $permintaan,
            'Persetujuan.Ditolak',
            "Permintaan persetujuan {$permintaan->JenisEntitas} Anda ditolak.".($catatan ? " Catatan: {$catatan}" : ''),
        );

        return $permintaan->refresh();
    }
}
