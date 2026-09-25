<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Application\Actions;

use App\Core\Audit\LayananAudit;
use App\Core\Entitas\RegistriEntitas;
use App\Domain\Persetujuan\Application\Services\LayananNotifikasiPersetujuan;
use App\Domain\Persetujuan\Application\Services\LayananPenyetuju;
use App\Domain\Persetujuan\Application\Services\PemilihTahapPersetujuan;
use App\Domain\Persetujuan\Domain\Enums\JenisKeputusanPersetujuan;
use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\KeputusanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Contracts\TransaksiDatabase;
use App\Shared\Domain\Exceptions\AksesDitolak;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

final class SetujuiPermintaanPersetujuan
{
    public function __construct(
        private readonly TransaksiDatabase $transaksi,
        private readonly LayananPenyetuju $layananPenyetuju,
        private readonly RegistriEntitas $registriEntitas,
        private readonly LayananAudit $layananAudit,
        private readonly LayananNotifikasiPersetujuan $layananNotifikasiPersetujuan,
        private readonly PemilihTahapPersetujuan $pemilihTahap,
    ) {}

    /**
     * @return array{permintaan: PermintaanPersetujuan, selesai: bool}
     */
    public function jalankan(PermintaanPersetujuan $permintaan, Pengguna $penyetuju, ?string $catatan): array
    {
        if ($permintaan->Status !== StatusPermintaanPersetujuan::Menunggu->value) {
            throw new AturanBisnisDilanggar('Permintaan persetujuan ini sudah selesai.');
        }

        $tahap = TahapPersetujuan::query()
            ->where('AlurPersetujuanId', $permintaan->AlurPersetujuanId)
            ->where('Urutan', $permintaan->TahapSaatIni)
            ->firstOrFail();

        $entitas = $this->registriEntitas->cariEntitas($permintaan->JenisEntitas, $permintaan->EntitasId);

        if (! $this->layananPenyetuju->bolehMemutuskan($tahap, $entitas, $penyetuju, $permintaan->DimintaOleh)) {
            throw new AksesDitolak('Anda tidak berhak menyetujui tahap ini.');
        }

        if (KeputusanPersetujuan::query()->where('PermintaanPersetujuanId', $permintaan->Id)->where('TahapPersetujuanId', $tahap->Id)->where('PenyetujuId', $penyetuju->Id)->exists()) {
            throw new KonflikData('Anda sudah memutuskan tahap ini sebelumnya.');
        }

        $selesai = false;
        $tahapBerikutnya = null;

        $this->transaksi->jalankan(function () use ($permintaan, $tahap, $penyetuju, $catatan, &$selesai, &$tahapBerikutnya): void {
            KeputusanPersetujuan::create([
                'PermintaanPersetujuanId' => $permintaan->Id,
                'TahapPersetujuanId' => $tahap->Id,
                'PenyetujuId' => $penyetuju->Id,
                'Keputusan' => JenisKeputusanPersetujuan::Disetujui->value,
                'Catatan' => $catatan,
                'DiputuskanPada' => now(),
            ]);

            $jumlahSetuju = KeputusanPersetujuan::query()
                ->where('PermintaanPersetujuanId', $permintaan->Id)
                ->where('TahapPersetujuanId', $tahap->Id)
                ->where('Keputusan', JenisKeputusanPersetujuan::Disetujui->value)
                ->count();

            if ($jumlahSetuju < $tahap->JumlahMinimumPenyetuju) {
                return;
            }

            $tahapBerikutnya = $this->pemilihTahap->tahapBerikutnya(
                $permintaan->AlurPersetujuanId,
                $tahap->Urutan,
                PemilihTahapPersetujuan::nilaiDari($permintaan->DataTambahan),
            );

            if ($tahapBerikutnya) {
                $permintaan->TahapSaatIni = $tahapBerikutnya->Urutan;
            } else {
                $permintaan->Status = StatusPermintaanPersetujuan::Disetujui->value;
                $permintaan->SelesaiPada = now()->toImmutable();
                $selesai = true;
            }

            $permintaan->save();
        });

        $this->layananAudit->catat(
            aksi: 'PermintaanPersetujuan.Disetujui',
            jenisEntitas: $permintaan->JenisEntitas,
            entitasId: $permintaan->EntitasId,
            dataSesudah: ['PermintaanPersetujuanId' => $permintaan->Id, 'TahapPersetujuanId' => $tahap->Id, 'PenyetujuId' => $penyetuju->Id],
        );

        if ($selesai) {
            $this->layananNotifikasiPersetujuan->beriTahuSelesai(
                $permintaan,
                'Persetujuan.Disetujui',
                "Permintaan persetujuan {$permintaan->JenisEntitas} Anda telah disetujui.",
            );
        } elseif ($tahapBerikutnya) {
            $this->layananNotifikasiPersetujuan->beriTahuTahapBaru($permintaan, $tahapBerikutnya, $entitas);
        }

        return ['permintaan' => $permintaan->refresh(), 'selesai' => $selesai];
    }
}
