<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Application\Actions;

use App\Domain\SiklusAset\Infrastructure\Persistence\Models\DetailPenghapusanAset;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;
use App\Shared\Domain\Exceptions\KonflikData;

final class TambahDetailPenghapusanAset
{
    public function jalankan(PengajuanPenghapusanAset $pengajuan, string $asetId, ?float $nilaiBukuSaatPenghapusan, ?float $hasilPelepasan, ?string $catatan): DetailPenghapusanAset
    {
        if ($pengajuan->Status !== PengajuanPenghapusanAset::STATUS_DRAFT) {
            throw new AturanBisnisDilanggar('Detail aset hanya boleh ditambahkan selagi pengajuan masih berupa draft.');
        }

        if (DetailPenghapusanAset::query()->where('PengajuanPenghapusanAsetId', $pengajuan->Id)->where('AsetId', $asetId)->exists()) {
            throw new KonflikData('Aset ini sudah ada dalam pengajuan penghapusan.');
        }

        return DetailPenghapusanAset::create([
            'OrganisasiId' => $pengajuan->OrganisasiId,
            'PengajuanPenghapusanAsetId' => $pengajuan->Id,
            'AsetId' => $asetId,
            'NilaiBukuSaatPenghapusan' => $nilaiBukuSaatPenghapusan,
            'HasilPelepasan' => $hasilPelepasan,
            'Status' => DetailPenghapusanAset::STATUS_MENUNGGU,
            'Catatan' => $catatan,
        ]);
    }
}
