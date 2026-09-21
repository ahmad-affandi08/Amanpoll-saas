<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Infrastructure\Listeners;

use App\Core\Audit\LayananAudit;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PermintaanPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PesananPembelian;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\PermintaanPersetujuan;

final class SinkronkanStatusPersetujuanPerencanaanPengadaan
{
    public function __construct(private readonly LayananAudit $audit) {}

    public function updated(PermintaanPersetujuan $permintaan): void
    {
        if (! $permintaan->wasChanged('Status')) {
            return;
        }

        match ($permintaan->JenisEntitas) {
            'Anggaran' => $this->sinkronkanAnggaran($permintaan),
            'UsulanAset' => $this->sinkronkanUsulan($permintaan),
            'PermintaanPembelian' => $this->sinkronkanPermintaanPembelian($permintaan),
            'PesananPembelian' => $this->sinkronkanPesananPembelian($permintaan),
            default => null,
        };
    }

    private function sinkronkanAnggaran(PermintaanPersetujuan $permintaan): void
    {
        $anggaran = Anggaran::query()->find($permintaan->EntitasId);
        if (! $anggaran) {
            return;
        }

        $status = match ($permintaan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => Anggaran::STATUS_AKTIF,
            PermintaanPersetujuan::STATUS_DITOLAK => Anggaran::STATUS_DITOLAK,
            default => null,
        };

        if ($status !== null) {
            $anggaran->Status = $status;
            $anggaran->save();
            $this->audit->catat('Anggaran.StatusPersetujuanDisinkronkan', 'Anggaran', $anggaran->Id, dataSesudah: ['Status' => $status]);
        }
    }

    private function sinkronkanUsulan(PermintaanPersetujuan $permintaan): void
    {
        $usulan = UsulanAset::query()->find($permintaan->EntitasId);
        if (! $usulan) {
            return;
        }

        $status = match ($permintaan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => UsulanAset::STATUS_DISETUJUI,
            PermintaanPersetujuan::STATUS_DITOLAK => UsulanAset::STATUS_DITOLAK,
            default => null,
        };

        if ($status !== null) {
            $usulan->Status = $status;
            $usulan->save();
            $this->audit->catat('UsulanAset.StatusPersetujuanDisinkronkan', 'UsulanAset', $usulan->Id, dataSesudah: ['Status' => $status]);
        }
    }

    private function sinkronkanPermintaanPembelian(PermintaanPersetujuan $permintaan): void
    {
        $entitas = PermintaanPembelian::query()->find($permintaan->EntitasId);
        if (! $entitas) {
            return;
        }
        $status = match ($permintaan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => PermintaanPembelian::STATUS_DISETUJUI,
            PermintaanPersetujuan::STATUS_DITOLAK => PermintaanPembelian::STATUS_DITOLAK,
            default => null,
        };
        if ($status !== null) {
            $entitas->Status = $status;
            $entitas->save();
            $this->audit->catat('PermintaanPembelian.StatusPersetujuanDisinkronkan', 'PermintaanPembelian', $entitas->Id, dataSesudah: ['Status' => $status]);
        }
    }

    private function sinkronkanPesananPembelian(PermintaanPersetujuan $permintaan): void
    {
        $entitas = PesananPembelian::query()->find($permintaan->EntitasId);
        if (! $entitas) {
            return;
        }
        $status = match ($permintaan->Status) {
            PermintaanPersetujuan::STATUS_DISETUJUI => PesananPembelian::STATUS_DISETUJUI,
            PermintaanPersetujuan::STATUS_DITOLAK => PesananPembelian::STATUS_DITOLAK,
            default => null,
        };
        if ($status !== null) {
            $entitas->Status = $status;
            $entitas->save();
            $this->audit->catat('PesananPembelian.StatusPersetujuanDisinkronkan', 'PesananPembelian', $entitas->Id, dataSesudah: ['Status' => $status]);
        }
    }
}
