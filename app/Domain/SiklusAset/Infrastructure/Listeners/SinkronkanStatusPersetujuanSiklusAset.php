<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Infrastructure\Listeners;

use App\Domain\Persetujuan\Domain\Enums\StatusPermintaanPersetujuan;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;
use App\Domain\SiklusAset\Domain\Enums\StatusPengajuanPenghapusanAset;
use App\Domain\SiklusAset\Domain\Enums\StatusPermintaanMutasiAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PermintaanMutasiAset;

/** Mesin Persetujuan (FASE 06) sengaja domain-agnostic. */
final class SinkronkanStatusPersetujuanSiklusAset
{
    public function updated(PermintaanPersetujuan $permintaanPersetujuan): void
    {
        if (! $permintaanPersetujuan->wasChanged('Status')) {
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
        if (! $permintaan) {
            return;
        }

        match ($permintaanPersetujuan->Status) {
            StatusPermintaanPersetujuan::Disetujui->value => $this->terapkan($permintaan, StatusPermintaanMutasiAset::Disetujui->value, true),
            StatusPermintaanPersetujuan::Ditolak->value => $this->terapkan($permintaan, StatusPermintaanMutasiAset::Ditolak->value, false),
            default => null,
        };
    }

    private function sinkronkanPenghapusan(PermintaanPersetujuan $permintaanPersetujuan): void
    {
        /** @var PengajuanPenghapusanAset|null $pengajuan */
        $pengajuan = PengajuanPenghapusanAset::query()->find($permintaanPersetujuan->EntitasId);
        if (! $pengajuan) {
            return;
        }

        match ($permintaanPersetujuan->Status) {
            StatusPermintaanPersetujuan::Disetujui->value => $this->terapkan($pengajuan, StatusPengajuanPenghapusanAset::Disetujui->value, true),
            StatusPermintaanPersetujuan::Ditolak->value => $this->terapkan($pengajuan, StatusPengajuanPenghapusanAset::Ditolak->value, false),
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
