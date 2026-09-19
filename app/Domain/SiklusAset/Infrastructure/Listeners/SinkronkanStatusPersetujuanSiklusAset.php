<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Listeners;

use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;

/**
 * Mesin Persetujuan (FASE 06) sengaja domain-agnostic -- tidak tahu apa-apa
 * soal PermintaanMutasiAset/PengajuanPenghapusanAset. Observer ini yang
 * menyalin balik hasil keputusan (Disetujui/Ditolak) ke status entitas
 * SiklusAset sendiri, TANPA mengeksekusi apa pun -- eksekusi (pemindahan
 * lokasi/pengarsipan aset) tetap aksi manual terpisah (lihat
 * EksekusiMutasiAset/EksekusiPenghapusanAset) karena tindakan fisiknya
 * bisa tertunda dari momen keputusan disetujui.
 */
final class SinkronkanStatusPersetujuanSiklusAset
{
    public function updated(PermintaanPersetujuan $permintaanPersetujuan): void
    {
        if (!$permintaanPersetujuan->wasChanged('Status')) {
            return;
        }

        match ($permintaanPersetujuan->JenisEntitas) {
            'PermintaanMutasiAset' => $this->sinkronkanMutasi($permintaanPersetujuan),
            'PengajuanPenghapusanAset' => $this->sinkronkanPenghapusan($permintaanPersetujuan),
            default => null,
        };
    }

    private function sinkronkanMutasi(PermintaanPersetujuan $permintaanPersetujuan): void
    {
        /** @var PermintaanMutasiAset|null $permintaan */
        $permintaan = PermintaanMutasiAset::query()->find($permintaanPersetujuan->EntitasId);
        if (!$permintaan) {
            return;
        }

        match ($permintaanPersetujuan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => $this->terapkan($permintaan, PermintaanMutasiAset::STATUS_DISETUJUI, true),
            PermintaanPersetujuan::STATUS_DITOLAK => $this->terapkan($permintaan, PermintaanMutasiAset::STATUS_DITOLAK, false),
            default => null,
        };
    }

    private function sinkronkanPenghapusan(PermintaanPersetujuan $permintaanPersetujuan): void
    {
        /** @var PengajuanPenghapusanAset|null $pengajuan */
        $pengajuan = PengajuanPenghapusanAset::query()->find($permintaanPersetujuan->EntitasId);
        if (!$pengajuan) {
            return;
        }

        match ($permintaanPersetujuan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => $this->terapkan($pengajuan, PengajuanPenghapusanAset::STATUS_DISETUJUI, true),
            PermintaanPersetujuan::STATUS_DITOLAK => $this->terapkan($pengajuan, PengajuanPenghapusanAset::STATUS_DITOLAK, false),
            default => null,
        };
    }

    private function terapkan(PermintaanMutasiAset|PengajuanPenghapusanAset $entitas, string $status, bool $disetujui): void
    {
        $entitas->Status = $status;
        if ($entitas instanceof PermintaanMutasiAset && $disetujui) {
            $entitas->DisetujuiPada = now()->toImmutable();
        }
        $entitas->save();
    }
}
