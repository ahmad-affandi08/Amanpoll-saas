<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Application\Services;

use App\Domain\Aset\Domain\Enums\JenisMeterAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\MeterAset;
use App\Domain\Aset\Infrastructure\Persistence\Models\PembacaanMeterAset;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\RencanaPemeliharaanAset;

/**
 * Membaca meter yang menjadi pemicu rencana preventif berbasis pemakaian.
 *
 * Hanya meter kumulatif yang sah: ambang "setiap 500 jam" berarti selisih dari
 * pembacaan saat servis terakhir, dan selisih itu tidak bermakna pada meter
 * yang naik-turun seperti suhu atau tekanan.
 */
final class PembacaMeterPreventif
{
    /**
     * Meter yang dipilih saat aset ditetapkan, atau satu-satunya meter kumulatif aktif
     * milik aset. Aset dengan beberapa meter kumulatif wajib memilih; menebak salah
     * satunya berarti servis mengikuti jam yang keliru.
     */
    public function meterUntuk(RencanaPemeliharaanAset $asetPlan): ?MeterAset
    {
        if ($asetPlan->MeterAsetId !== null) {
            return MeterAset::query()
                ->whereKey($asetPlan->MeterAsetId)
                ->where('AsetId', $asetPlan->AsetId)
                ->where('Jenis', JenisMeterAset::Kumulatif->value)
                ->where('Aktif', true)
                ->first();
        }

        return $this->satuSatunyaMeter($asetPlan->AsetId);
    }

    public function satuSatunyaMeter(string $asetId): ?MeterAset
    {
        $daftar = MeterAset::query()
            ->where('AsetId', $asetId)
            ->where('Jenis', JenisMeterAset::Kumulatif->value)
            ->where('Aktif', true)
            ->limit(2)
            ->get();

        return $daftar->count() === 1 ? $daftar->first() : null;
    }

    /**
     * Pembacaan terakhir sampai saat ini, atau nilai awal meter bila belum pernah dibaca.
     * Pembacaan bertanggal depan (salah ketik jam) tidak boleh memicu servis lebih awal.
     */
    public function nilaiTerkini(MeterAset $meter): float
    {
        $nilai = PembacaanMeterAset::query()
            ->where('MeterAsetId', $meter->Id)
            ->where('DibacaPada', '<=', now())
            ->orderByDesc('DibacaPada')
            ->value('Nilai');

        return (float) ($nilai ?? $meter->NilaiAwal);
    }
}
