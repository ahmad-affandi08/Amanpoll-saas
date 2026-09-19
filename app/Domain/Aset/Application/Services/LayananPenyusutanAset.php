<?php

declare(strict_types=1);

namespace App\Domain\Aset\Application\Services;

use App\Domain\Aset\Infrastructure\Persistence\Models\Aset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use Carbon\CarbonInterface;

/**
 * Kalkulasi penyusutan garis lurus MURNI SEBAGAI PRATINJAU -- hasilnya
 * dipakai untuk mengisi awal form NilaiAset, tapi baris yang benar-benar
 * tersimpan selalu lewat input eksplisit (BuatNilaiAset), sama seperti
 * pratinjau() pada LayananNomorDokumen. Tidak ada penjadwalan otomatis
 * yang menulis NilaiAset tanpa keterlibatan pengguna.
 */
final class LayananPenyusutanAset
{
    /**
     * @return array{NilaiBuku: float, AkumulasiPenyusutan: float, BebanPenyusutanPeriode: float}
     */
    public function hitungGarisLurus(Aset $aset, CarbonInterface $tanggal): array
    {
        $hargaPerolehan = $aset->HargaPerolehan;
        $umurManfaatBulan = $aset->UmurManfaatBulan;
        $tanggalMulai = $aset->TanggalMulaiOperasi ?? $aset->TanggalPerolehan;

        if ($hargaPerolehan === null || $umurManfaatBulan === null || $umurManfaatBulan < 1 || $tanggalMulai === null) {
            throw new AturanBisnisDilanggar('Harga perolehan, umur manfaat, dan tanggal mulai operasi/perolehan aset harus diisi untuk menghitung penyusutan.');
        }

        $hargaPerolehan = (float) $hargaPerolehan;
        $nilaiResidu = (float) ($aset->NilaiResidu ?? 0);
        $bebanBulanan = ($hargaPerolehan - $nilaiResidu) / $umurManfaatBulan;

        $bulanBerjalan = max(0, min($umurManfaatBulan, $tanggalMulai->diffInMonths($tanggal, false)));

        $akumulasiPenyusutan = round($bebanBulanan * $bulanBerjalan, 2);
        $nilaiBuku = round($hargaPerolehan - $akumulasiPenyusutan, 2);

        return [
            'NilaiBuku' => max($nilaiBuku, $nilaiResidu),
            'AkumulasiPenyusutan' => $akumulasiPenyusutan,
            'BebanPenyusutanPeriode' => round($bebanBulanan, 2),
        ];
    }
}
